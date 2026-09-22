TOETAP V2.2.26 — AUTO BACKGROUND REMOVAL (PROTOTYPE)

Flow:
Take/Upload → on-device AI background removal → preview → choose Background Removed or Original → save.

Implementation:
- Browser-side @imgly/background-removal 1.5.6.
- First use downloads/caches the AI runtime/model and can take noticeably longer.
- Processed output is transparent PNG.
- Original image is stored separately in original_photo_path.
- If AI/model loading fails, the user can still save the original photo.
- Existing custom_photo_path remains the image used throughout TOETAP.
- No NFC, Strava, webhook, matching, activity or tag-ownership changes.

INSTALL:
1. V2.2.23 migration must already exist.
2. Run v226_auto_background_removal.sql once.
3. Deploy files.

IMPORTANT COMMERCIAL LICENSE NOTE:
The browser background-removal library used in this prototype is AGPL licensed.
Before commercial/proprietary TOETAP distribution, review the AGPL obligations or
replace/license the processing engine appropriately. The TOETAP upload/storage
architecture is intentionally separate so the engine can be swapped later.
