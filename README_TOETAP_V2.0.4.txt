TOETAP V2.0.4 — VALUE UNLOCKED

Replaces Cost/km economics with a game-like Value Unlocked system.

Formula:
Value Unlocked = Purchase Price × min(Current Mileage / Target Mileage, 1)

Example:
Price THB 4,000, target 400 km, current 10 km
= THB 100 / THB 4,000 unlocked (2.5%)

UI:
- Shoes cards: RUN x / target km + VALUE UNLOCKED + progress.
- Shoe detail: VALUE UNLOCKED and a dedicated unlock progress bar.
- At 100%: FULLY UNLOCKED.
- Value is capped at purchase price after target mileage; shoe remains usable.
- Purchase price remains editable.
- Cost/km fields removed.

No SQL changes required after V2.0 migration.
