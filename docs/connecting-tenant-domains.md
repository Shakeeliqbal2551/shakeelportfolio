# Connecting a tenant's custom domain (Hostinger)

Manual, per-domain process for shared Hostinger hosting — no automation yet
(SSL/DNS automation would need root/API access this hosting tier doesn't
give us; see [docs/saas-admin-dashboard-plan.md](saas-admin-dashboard-plan.md)
for the broader admin-panel context). Do this every time a tenant's
custom-domain request needs attaching.

The app itself lives at (confirm with the `find` command in step 1 — don't
assume, the exact path depends on how the hosting account is laid out):
```
/home/<account>/domains/shakeeliqbal.com/public_html
```

---

## 1. Server-side: symlink the new domain to the same app (SSH)

First, confirm the real app root — don't guess:
```bash
find /home/<account>/domains -maxdepth 6 -iname "artisan" 2>/dev/null
```
That directory (the one containing `artisan`) is the app root; the Laravel
`public/` folder lives directly inside it.

SSH in as `<account>`, then run:

```bash
rm -rf /home/<account>/domains/<NEWDOMAIN>/public_html
ln -s /home/<account>/domains/shakeeliqbal.com/public_html/public /home/<account>/domains/<NEWDOMAIN>/public_html
```

Replace `<NEWDOMAIN>` with the real domain, e.g. `janedeveloper.com`.

**Verify it's a real, resolving symlink — not a dangling one:**
```bash
ls -la /home/<account>/domains/<NEWDOMAIN>/public_html/
readlink -f /home/<account>/domains/<NEWDOMAIN>/public_html
```
The `ls` must list real Laravel `public/` contents (`index.php`, `.htaccess`,
`build/`, etc.) — if it says "No such file or directory", the symlink target
path is wrong. Re-run the `find` above and re-symlink to `<that folder>/public`.

**Never edit `.env` / `APP_URL` for this.** `APP_URL` must always stay the
platform's own canonical domain (`shakeeliqbal.com`, or `app.shakeeliqbal.com`
if the platform is split onto its own subdomain). The app uses it as the
default for `PLATFORM_HOSTS` — the setting that tells `ResolveTenantDomain`
"is this host the platform itself, or a tenant's custom domain" (see
[config/portfolio.php](../config/portfolio.php) and
[app/Http/Middleware/ResolveTenantDomain.php](../app/Http/Middleware/ResolveTenantDomain.php)).
Setting `APP_URL` to a tenant's domain breaks that check for every tenant,
not just this one. In production, set `PLATFORM_HOSTS` explicitly in `.env`
rather than relying on the `APP_URL` fallback:
```
PLATFORM_HOSTS="shakeeliqbal.com,app.shakeeliqbal.com"
```

## 2. hPanel: add the domain as a website

1. hPanel → **Websites** → **Add Website** (or it may already exist if the
   domain was bought/added through Hostinger — check first under
   **Websites** or **Domains**).
2. Point it at the same account/PHP settings as the main app.
3. **Set the PHP version to match the main `shakeeliqbal.com` site exactly**
   (this app requires PHP `^8.3` per `composer.json` — check what the main
   site is actually running and match it). A newly auto-provisioned domain
   slot often defaults to a lower PHP version, which throws a Composer
   platform-requirement error ("Composer detected issues... require PHP
   >= 8.3") even though the symlink/files are correct. Check under the
   domain's own **PHP Configuration** in hPanel and change it if it doesn't
   match.

## 3. DNS: point the domain at this server

hPanel → **Domains** → `<NEWDOMAIN>` → **DNS / Nameservers** → **DNS records**.

If the domain was newly added to Hostinger, it likely auto-created an
**ALIAS** record on `@` pointing at `<domain>.cdn.hstgr.net` — Hostinger's own
generic placeholder/CDN service, NOT this app. That's what serves a Hostinger
"This Page Does Not Exist" 404 page (a static file, not from Laravel) if left
in place. Fix it:

1. **Delete** the `ALIAS | @ | <domain>.cdn.hstgr.net` record (delete, don't
   edit — hPanel won't let an A record coexist with an ALIAS on the same
   name, you'll get a validation error if you try to edit it in place).
2. **Add** a new record:
   - Type: `A`
   - Name: `@`
   - Value: `<this server's IP>` (same for every tenant, one shared-hosting
     server)
   - TTL: `300` (fast propagation) or leave default
3. Also fix the `www` CNAME if you want `www.<domain>` to work too — change
   its value from `www.<domain>.cdn.hstgr.net` to just `<domain>` (so it
   follows the root). Optional; most portfolio links are shared without
   `www.`.
4. Leave `MX`, `TXT` (other than the verification TXT from step 5), `autodiscover`,
   `autoconfig`, `ftp` records alone — unrelated mail/FTP config.

**Verify propagation** before assuming something's broken:
```bash
nslookup <NEWDOMAIN>
```
should return the server IP. Also check https://www.whatsmydns.net for
global propagation. TTL 300 is usually fast (minutes), but can occasionally
take longer depending on the registrar/resolver.

## 4. SSL

hPanel → domain → **SSL** → **Install SSL / Run AutoSSL**. Free, automatic,
works for any domain resolving to this server regardless of where it was
bought — but only succeeds once the DNS A record (step 3) has actually
propagated. If it fails, wait and retry; don't debug anything else until DNS
is confirmed resolving.

## 5. Verify the app itself, then verify domain ownership in the admin panel

1. Visit `http://<NEWDOMAIN>` yourself (plain http first — Hostinger/LiteSpeed
   auto-redirects http → https, that's normal and not a problem). At this
   point the app is reachable but **not yet routed to the right tenant** —
   this app requires DNS-proven ownership before a domain resolves to any
   portfolio, to stop anyone pointing a stray domain at the server and
   seeing another tenant's content (see
   [app/Services/DomainVerificationService.php](../app/Services/DomainVerificationService.php)).
   Expect a "domain not configured" page here, not a 404 and not the wrong
   tenant's site.
2. In this app: **`/admin/tenants` → find the tenant → Manage Domains → Add
   Domain** (if not already added). This generates a `verification_token`.
3. Add a DNS **TXT** record:
   - Name: `_portfolio-verify.<NEWDOMAIN>`
   - Value: the token shown in the admin panel
4. Click **Check DNS** on the domain row (or run
   `php artisan domains:verify <NEWDOMAIN>` over SSH). Once
   `verification_status` flips to `verified`, the domain resolves to the
   tenant's portfolio immediately — verified is the activation state, there's
   no separate "go live" toggle.
5. Once step 4's SSL cert is confirmed issued, click **Mark SSL Issued** in
   the admin panel — this is bookkeeping only, it doesn't affect routing.

Note: unlike some other setups, tenants don't request domains themselves
through a self-serve UI — only the admin adds domains via the admin panel.
The DNS-TXT step above is something you do yourself (or walk the tenant
through) when onboarding them, not something they submit through the app.

---

## Troubleshooting quick reference

| Symptom | Cause | Fix |
|---|---|---|
| Hostinger-branded 404 ("This Page Does Not Exist", skateboarder graphic), static `Last-Modified` date, no PHP/Laravel headers in response | DNS still pointing at an `ALIAS`/CDN placeholder, not this server | Step 3 — delete the ALIAS, add an A record |
| `ls -la .../public_html/` says "No such file or directory" despite `ls -l` showing a symlink | Dangling symlink — target path is wrong | Step 1 — re-find the real path via `find ... -iname artisan`, re-symlink |
| "Composer detected issues... require PHP >= 8.3" | This domain's PHP version in hPanel doesn't match the app's requirement | Step 2 — set PHP version to match the main site |
| App loads a "domain not configured" page | Domain not yet added/verified in the admin panel | Step 5 — add the domain, add the TXT record, click Check DNS |
| App loads, but shows the WRONG tenant's portfolio | Domain row in `/admin/tenants/*/domains` is attached to the wrong tenant | Check `/admin/tenants` for which tenant the domain is actually attached to; remove and re-add under the correct tenant |
| Everything above checks out but still doesn't load | DNS not propagated yet | `nslookup <domain>`, check whatsmydns.net, wait |
