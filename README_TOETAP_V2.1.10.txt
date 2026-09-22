TOETAP V2.1.10 — PERSONAL PROFILE CACHE FIX

Root cause confirmed:
- V2.1.9 displayed the profile from $toetapRoleRuns.
- But the existing Shoe Performance loop never populated that array.
- Also, Shoe Performance could load from its old session cache, meaning no raw activity loop ran at all.
Result: performance stats appeared, while PERSONAL SHOE PROFILE stayed hidden.

Fix:
- roleRuns is now stored inside the same $perf object used by SHOE PERFORMANCE PROFILE.
- Every Strava activity matching the physical shoe's gear_id is added during the proven performance scan.
- PERSONAL SHOE PROFILE reads $perf['roleRuns'] whether $perf came live or from cache.
- Performance cache key bumped to v2110, forcing one clean refresh and preventing old cache data from hiding the profile.
- No second Strava role-fetch path.
- No SQL migration.
