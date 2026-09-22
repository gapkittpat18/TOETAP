# TapSole — Strava-first, but not Strava-required

## Product rule
When Strava is connected, Strava is the default/primary source across TapSole:
- Recent runs
- Run history
- Distance / pace / time
- Shoe mileage
- Insights
- Automatic run matching
- Automatic Strava gear assignment

TapSole DB does NOT replace Strava as the primary data source for a connected user.

## Without Strava
The app must still be usable:
- Add/manage shoes
- Register/tap NFC
- Latest Tap Wins selection
- Manual run entry
- Manual mileage/history
- Retire/manage shoes
- Basic insights from locally entered activities

## Local activities table
Keep it for:
- Manual runs
- webhook audit/cache
- fallback
- future non-Strava sources

## Future source priority
V1: Strava primary.
No Strava: TapSole local fallback.
Future Garmin/Suunto/COROS/Apple Health integrations can be added without changing NFC selection logic.
