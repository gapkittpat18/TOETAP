TOETAP V1.8.5 — NEW TAG ACTIVATION LINK FIX
Problem:
When opening a new NFC through the new opaque URL (?t=...), tap.php looked up the tag correctly
but passed the empty legacy ?tag= query variable into newTag(). This generated:
shoe_add.php?tag=
with no ID.

Fix:
- tap.php now passes the actual database tag_code + public_token from the resolved tag row.
- New tags prefer shoe_add.php?t=<opaque public token>.
- Legacy ?tag=TSxxxxxx activation remains supported.
- No SQL migration.
- Full PHP syntax lint passed.

Additional audit fix:
- shoe_add.php now accepts the opaque ?t= token as well as legacy ?tag=.
- The opaque token is preserved through the activation POST.
