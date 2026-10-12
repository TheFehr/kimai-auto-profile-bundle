# AutoProfileBundle

A [Kimai](https://www.kimai.org/) plugin that picks the table profile
(Desktop / Mobile, under "Customize display") from the browser's screen
size instead of making you toggle it by hand.

## Why

Kimai keeps the Desktop/Mobile profile in the PHP session. It is only
restored from a cookie on a real form login, so every new session
(expiry, remember-me, cleared cookies) silently falls back to Desktop.

## How it works

- A tiny inline script (injected through `ThemeEvent::JAVASCRIPT`) writes
  `kimai_vp=mobile|desktop` to a cookie based on
  `matchMedia('(max-width: 767px)')` (Bootstrap's `md` breakpoint), and
  reloads once when the value changes. It also reacts to rotation/resizing.
- A request subscriber copies that cookie into Kimai's session profile on
  every request, so the right layout is rendered server-side on first paint.

The manual Desktop/Mobile toggle still exists, but it is overridden on the
next page load.

## Install

Kimai >= 2.0. Place this repository at `var/plugins/AutoProfileBundle`
(the directory name must match), then reload:

```
git clone <this repo> var/plugins/AutoProfileBundle
bin/console kimai:reload --env=prod
```

In Docker, mount or clone it into `/opt/kimai/var/plugins/AutoProfileBundle`
and run the reload inside the container.

## Configuration

None. To change the breakpoint, edit `MOBILE_MAX_WIDTH` in
`EventSubscriber/AutoProfileSubscriber.php`.

## Tested

Against `kimai/kimai2:apache` (2.x, Oct 2026): setting `kimai_vp` to `mobile`
or `desktop` flips the checked profile on `/en/timesheet/` in both directions.

## License

MIT
