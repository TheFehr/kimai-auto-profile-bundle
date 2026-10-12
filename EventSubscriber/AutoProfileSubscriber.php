<?php

namespace KimaiPlugin\AutoProfileBundle\EventSubscriber;

use App\Event\ThemeEvent;
use App\Utils\ProfileManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Picks the datatable profile (desktop/mobile) from the browser's viewport width.
 *
 * The browser reports its viewport in the "kimai_vp" cookie (see the injected script),
 * this subscriber copies it into the session profile on every request.
 */
final class AutoProfileSubscriber implements EventSubscriberInterface
{
    private const COOKIE = 'kimai_vp';
    // Bootstrap "md" breakpoint, below this width Kimai's own mobile layout kicks in
    private const MOBILE_MAX_WIDTH = 767;

    public function __construct(private ProfileManager $profileManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // after the session listener (128), before the controller
            KernelEvents::REQUEST => ['onRequest', 0],
            ThemeEvent::JAVASCRIPT => ['onJavascript', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // never start a session for stateless (API) requests
        if ($request->attributes->getBoolean('_stateless') || !$request->hasSession()) {
            return;
        }

        $profile = $request->cookies->get(self::COOKIE);
        if (!\is_string($profile) || !$this->profileManager->isValidProfile($profile)) {
            return;
        }

        $session = $request->getSession();
        if ($this->profileManager->getProfileFromSession($session) !== $profile) {
            $this->profileManager->setProfile($session, $profile);
        }
    }

    public function onJavascript(ThemeEvent $event): void
    {
        $cookie = self::COOKIE;
        $max = self::MOBILE_MAX_WIDTH;

        $event->addContent(<<<HTML
<script type="text/javascript">
(function () {
    var mq = window.matchMedia('(max-width: {$max}px)');
    function sync() {
        var want = mq.matches ? 'mobile' : 'desktop';
        var m = document.cookie.match(/(?:^|; ){$cookie}=([^;]*)/);
        if (m && m[1] === want) { return; }
        document.cookie = '{$cookie}=' + want + '; path=/; max-age=31536000; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        // cookies blocked? don't reload-loop
        if (document.cookie.indexOf('{$cookie}=' + want) === -1) { return; }
        location.reload();
    }
    sync();
    mq.addEventListener('change', sync);
})();
</script>
HTML);
    }
}
