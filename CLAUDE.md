# denisecalvin.com

One-page site for Denise Calvin's Bookkeeping and Financial Services (Castries, St. Lucia).

**This site intentionally deviates from the repo's Astro pattern** — it is plain HTML/CSS/JS with no build step, per the owner's request.

- Webroot is `www/` — deploy by uploading its contents to the host.
- `www/images/` holds full-size original images (untouched source of truth); `www/images/web/` holds the resized/compressed copies the site actually references. Regenerate with `sips` if originals change.
- `www/contact.php` emails form submissions to nattyden@hotmail.com. SMTP credentials go in `www/config.local.php` (gitignored) — copy `www/config.local.php.example`. Without it, it falls back to PHP `mail()`.
- The form only works on a PHP host; preview locally with `php -S localhost:8080 -t www` (or a plain static server, where the form 404s).
- Menu links are anchors to homepage sections (`#what-we-do`, `#about`, `#contact`). The About bio is placeholder text awaiting real copy.
