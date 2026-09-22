TOETAP V2.1.4 — COMPARE SHOE NAME FIX

Root cause:
Compare used shoe_library.brand/model for display names. Many physical user_shoes rows
store their actual names in custom_brand/custom_model and may have no shoe_library_id,
so those rows rendered as blank names such as "· 0 km".

Fix:
- Uses the same naming priority as the real Shoes/Shoe pages:
  nickname -> custom_brand + custom_model -> library brand + model -> Shoe #ID.
- Keeps all user-owned physical shoes in Compare.
- Always appends physical shoe ID to distinguish duplicate pairs.
- Retired shoes remain labeled RETIRED.
- No SQL migration.
