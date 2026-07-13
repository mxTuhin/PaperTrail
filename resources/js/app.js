// Vite entry point. Bundled app-level JS lives here.
//
// The Alpine theme store is intentionally NOT registered here: it must run as
// a classic, non-deferred inline script (see resources/views/partials/
// theme-store.blade.php) so its `alpine:init` listener attaches before the
// deferred Alpine CDN bundle boots. A module script cannot guarantee that
// ordering relative to the classic `defer` Alpine tag.
