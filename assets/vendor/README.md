# Vendored front-end libraries

These files are unmodified upstream distributions, committed to the repository
rather than loaded from a CDN.

| Library | Version | Files | Upstream |
|---|---|---|---|
| Bootstrap | 5.3.3 | `bootstrap/bootstrap.min.css`, `bootstrap/bootstrap.bundle.min.js` | [getbootstrap.com](https://getbootstrap.com) |
| Bootstrap Icons | 1.11.3 | `bootstrap-icons/bootstrap-icons.min.css`, `bootstrap-icons/fonts/*` | [icons.getbootstrap.com](https://icons.getbootstrap.com) |
| FullCalendar | 6.1.15 | `fullcalendar/index.global.min.js` | [fullcalendar.io](https://fullcalendar.io) |
| Chart.js | 4.4.6 | `chartjs/chart.umd.js` | [chartjs.org](https://www.chartjs.org) |

Total: about 1.2 MB.

## Why they are here

**The system has to work without internet access.** RePawter is deployed on
barangay hardware and may be reached over a local network. When the CDN was
unreachable, every page rendered as unstyled HTML — no grid, no cards, no
buttons — which is indistinguishable from the site being broken.

**It lets the Content-Security-Policy forbid every external origin.** With
nothing loaded from a third party, `script-src` and `style-src` are `'self'`
only. That is a materially stronger policy than allowing a CDN, which has to be
trusted not to serve something else tomorrow.

**It makes deployments reproducible.** A CDN can withdraw a version or change
what a URL returns; a committed file cannot.

The cost is repository size and manual updates. For an application of this size,
served to a community over a connection that may not be reliable, that is the
right trade.

> FullCalendar 6 has no separate stylesheet — it injects its own CSS, including
> a base64 icon font. That is why the CSP allows `font-src data:`; without it
> the calendar's toolbar buttons render as empty boxes.

## Updating

There is no build step. Install the version you want, copy the distribution
files in, and check the result:

```bash
npm install --no-save bootstrap@<version> bootstrap-icons@<version> \
                      fullcalendar@<version> chart.js@<version>

cp node_modules/bootstrap/dist/css/bootstrap.min.css         assets/vendor/bootstrap/
cp node_modules/bootstrap/dist/js/bootstrap.bundle.min.js    assets/vendor/bootstrap/
cp node_modules/bootstrap-icons/font/bootstrap-icons.min.css assets/vendor/bootstrap-icons/
cp node_modules/bootstrap-icons/font/fonts/*                 assets/vendor/bootstrap-icons/fonts/
cp node_modules/fullcalendar/index.global.min.js             assets/vendor/fullcalendar/
cp node_modules/chart.js/dist/chart.umd.js                   assets/vendor/chartjs/

npm run test:e2e
```

Then update the version numbers in the table above.

Things to check by hand after a Bootstrap upgrade, because they are not
automatic:

- `assets/css/paw-theme.css` re-points Bootstrap's CSS custom properties. Some
  component colours are compiled from Sass at build time and ignore those
  variables — `.btn-primary`, `.nav-pills`, `.list-group` are overridden
  explicitly for that reason. A new version may add more.
- `bootstrap-icons.min.css` references its fonts as `fonts/…`, so the `fonts/`
  directory must sit beside it.
- Icon names change between Bootstrap Icons releases. Every `bi-*` class used in
  the app must exist in `bootstrap-icons.json`, or it renders as nothing.
