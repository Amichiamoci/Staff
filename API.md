# Staff Portal HTTP API

Use the Portal installation URL with paths such as `/api/event` when the API is enabled.
The examples use `https://staff.example.invalid/api`.
Keep any installation prefix before `/api`, such as `/staff/api`.

## Authentication and responses

Send the active application token in the `App-Bearer` header on every request.
Use HTTPS and make requests from the Web App backend.
For profile, registration, and personal team requests, send the signed-in user's verified email in the POST body as `email`.
Derive the email from the Web App session and prevent browsers from choosing its value.
Email matching ignores surrounding whitespace and case.
Keep the application token and any Drive encryption key out of browsers, logs and persistent response caches.
When an administrator rotates the token, update the Web App backend to use the replacement.

Success returns HTTP `200` and the JSON array or object for the requested route.
Lists are JSON arrays and return `[]` when empty.
IDs are JSON integers.
Date and time fields use `YYYY-MM-DD` and `HH:MM:SS` in the event's local time, Europe/Rome.
Unset values are `null` where indicated.
Send the documented POST fields as `application/x-www-form-urlencoded`.
Simple MVC rejects unrecognized form fields before the API action runs.
Omit optional request fields when you have no value to send.

Errors return a JSON object with an `error` string.

```json
{
  "error": "This profile is unavailable."
}
```

| HTTP status | Meaning |
| --- | --- |
| `400` | Invalid input, body, or method |
| `401` | Missing, invalid, or revoked token |
| `403` | Profile access or save refused, subscriptions closed, or the subscription requires staff assistance |
| `404` | Unknown API resource |
| `500` | Request failed on the server |

An unregistered route, including any API route while the API is disabled, returns the Portal's standard HTML route error.
Use the status code for application logic.

## Web App logins

### `POST /api/login`

After a successful Web App sign-in, send the user's verified `email`.
Supply `ipAddress` as the user's IPv4 or IPv6 address observed by the Web App backend.
If omitted, the Portal uses its normal client IP lookup, which can use an address supplied by the Portal’s proxy.
Each successful call records one login for the application token used.
`isAdmin` is true when the email belongs to a profile associated with an active Portal administrator.

```sh
curl 'https://staff.example.invalid/api/login' \
  -H 'App-Bearer: REPLACE_WITH_APP_TOKEN' \
  --data-urlencode 'email=participant1@example.invalid' \
  --data-urlencode 'ipAddress=2001:db8::123'
```

```json
{
  "isAdmin": false
}
```

## Event and competition

```sh
curl 'https://staff.example.invalid/api/event' \
  -H 'App-Bearer: REPLACE_WITH_APP_TOKEN'
```

### `GET /api/event`

Returns the current edition, registration choices, fields, and document storage settings.
`edition` is `null` if no edition has been configured for the current year, and `motto` can be `null`.

```json
{
  "edition": {
    "id": 7,
    "year": 2026,
    "motto": "motto here"
  },
  "subscriptionsOpen": true,
  "parishes": [
    {
      "id": 12,
      "name": "Parish1Name"
    }
  ],
  "documentTypes": [
    {
      "id": 1,
      "label": "Carta d'Identità"
    }
  ],
  "shirtSizes": [
    "XXS",
    "XS",
    "S",
    "M",
    "L",
    "XL",
    "XXL",
    "3XL"
  ],
  "fields": [
    {
      "id": 3,
      "name": "Field1Name",
      "address": "1 Example Street",
      "latitude": 41.9,
      "longitude": 12.5
    }
  ],
  "storage": {
    "allowedSources": {
      "app:///": {
        "type": "built-in"
      }
    },
    "preferred": "app:///"
  }
}
```

### `GET /api/leaderboard`

Returns the current parish standings in rank order.

```json
[
  {
    "parishId": 12,
    "parishName": "Parish1Name",
    "points": 18,
    "rankPosition": 1
  }
]
```

### `GET /api/tournaments`

Returns current-edition tournaments.
`matchCount` counts all matches, including those awaiting a date or field.
`matchesWithResults` counts matches with a recorded score or a forfeit result.

```json
[
  {
    "id": 5,
    "name": "Tournament1Name",
    "sportId": 2,
    "sport": "Volleyball",
    "type": "Italiana (girone)",
    "teamCount": 2,
    "matchCount": 1,
    "matchesWithResults": 1
  }
]
```

### `GET /api/tournament_standings?tournament_id=5`

Returns the standings for the selected current-edition tournament, ordered by descending points.
Points and match counts can be `null` when unavailable.

```json
[
  {
    "teamId": 20,
    "teamName": "Team1Name",
    "parishId": 12,
    "points": 3,
    "matchesRemaining": 0,
    "matchesPlanned": 1
  }
]
```

### `POST /api/my_teams`

Send the verified `email` in the POST body.
Returns current team memberships with one item per team and managed person.

```sh
curl 'https://staff.example.invalid/api/my_teams' \
  -H 'App-Bearer: REPLACE_WITH_APP_TOKEN' \
  --data-urlencode 'email=participant1@example.invalid'
```

```json
[
  {
    "teamId": 20,
    "teamName": "Team1Name",
    "sportId": 2,
    "personId": 101
  }
]
```

### `GET /api/matches?team_id=20&tournament_id=5`

Both filters are optional and can be combined.
A team matches either side of the match.
Use `POST /api/my_teams` to find the user's team IDs.
`date`, `time`, and `fieldId` can be `null` while scheduling is pending.
`forfeitWinnerTeamId` identifies the awarded winner when a forfeit result exists and is otherwise `null`.
`scores` contains the recorded score entries in order, or `[]` for matches without scores.
Score values are strings.
Join `fieldId` to `fields[].id` from `GET /api/event` for the venue name, address, and optional coordinates.

```json
[
  {
    "id": 61,
    "tournamentId": 5,
    "date": "2026-06-20",
    "time": "17:30:00",
    "fieldId": 3,
    "forfeitWinnerTeamId": null,
    "homeTeam": {
      "id": 20,
      "name": "Team1Name"
    },
    "awayTeam": {
      "id": 21,
      "name": "Team2Name"
    },
    "scores": [
      {
        "home": "25",
        "away": "18"
      },
      {
        "home": "25",
        "away": "22"
      }
    ]
  }
]
```

### `GET /api/today_matches?parish_id=12`

Returns today's matches for the current edition where either team belongs to the specified parish.
Use a parish ID from `GET /api/event`.
Results use the same format as `GET /api/matches`, including recorded scores and forfeits.
A parish with no matches today receives `[]`.

## Document storage

Read the `storage` object from `GET /api/event` on the Web App backend before accepting identity documents and medical certificates.
With Drive enabled, use `drive:///` by default and use the same authorized Drive folder as the Portal.
Use `config.folderId` to identify the shared folder and configure the Web App backend's Drive credentials separately.
`preferred` names the default source in `allowedSources`.
When `encryptionKey` is a base64 string, decode it to 32 bytes and use it as the mount key with `richie314/simple-virtualfs` for compatible Drive reads and writes.
`encryptionKey` is `null` for an unencrypted Drive source.
Use the same canonical URI for the upload and the reference you submit, since encryption authenticates that URI.

The following `storage` object shows Drive enabled with a placeholder for the key.

```json
{
  "allowedSources": {
    "app:///": {
      "type": "built-in"
    },
    "drive:///": {
      "type": "google-drive",
      "config": {
        "folderId": "DRIVE_FOLDER_ID"
      },
      "encryptionKey": "BASE64_KEY_FROM_PORTAL"
    }
  },
  "preferred": "drive:///"
}
```

Store the file before submitting its URI.
Send an identity document as `documentUri` under `documenti/` and a medical certificate as `certificateUri` under `certificati/`.
Use a single unique filename with ASCII letters, digits, underscores or hyphens and a lowercase `.pdf` extension.
For example, `app:///documenti/documento_123abc.pdf` and `drive:///certificati/2026_789def.pdf` are valid references when their roots are allowed.
The Web App handles PDF preparation, file validation, storage, ownership, retrieval and orphan cleanup.
The API accepts references only.

## People and subscriptions

All requests in this section require the verified `email` in the POST body.
For profile and subscription lookups, the request body contains only the email.

```sh
curl 'https://staff.example.invalid/api/people' \
  -H 'App-Bearer: REPLACE_WITH_APP_TOKEN' \
  --data-urlencode 'email=participant1@example.invalid'
```

A managed profile is one whose email matches the signed-in user, or one whose current enrollment identifies a guardian with that email.
Use `POST /api/people` to discover profile IDs, `GET /api/event` for the current edition and choices, and `POST /api/subscriptions` to display registration status.

### `POST /api/people`

Returns the managed profiles.
`email`, `phone` and `documentExpiry` can be `null`.
`hasDocument` reports whether there is a saved reference.

```json
[
  {
    "id": 101,
    "firstName": "FirstName1",
    "lastName": "LastName1",
    "birthDate": "2000-04-05",
    "birthPlace": "Example City",
    "email": "participant1@example.invalid",
    "phone": null,
    "taxCode": "TAXCODE1",
    "documentTypeId": 1,
    "documentExpiry": "2030-04-05",
    "hasDocument": true
  }
]
```

### `POST /api/save_person`

Create a profile by omitting `id` and supplying `documentUri`.
The new profile uses the verified email.
To update a profile, supply its `id` and the same `taxCode`.
The existing email and tax code stay fixed.
`phone` is optional, as is `documentUri` when updating a profile.
An omitted optional field preserves the saved value on an update.
The remaining fields shown below are required.
The response contains one profile object, with the same fields as a list item from `POST /api/people`.

```sh
curl 'https://staff.example.invalid/api/save_person' \
  -H 'App-Bearer: REPLACE_WITH_APP_TOKEN' \
  --data-urlencode 'email=participant1@example.invalid' \
  --data-urlencode 'firstName=FirstName1' \
  --data-urlencode 'lastName=LastName1' \
  --data-urlencode 'birthDate=2000-04-05' \
  --data-urlencode 'birthPlace=Example City' \
  --data-urlencode 'taxCode=TAXCODE1' \
  --data-urlencode 'documentTypeId=1' \
  --data-urlencode 'documentExpiry=2030-04-05' \
  --data-urlencode 'documentUri=app:///documenti/documento_123abc.pdf'
```

### `POST /api/subscriptions`

Returns current-edition registrations for managed profiles even when subscriptions are closed.
A profile without a registration has no item in this list.
`guardianId` can be `null`.
`hasCertificate` reports whether there is a saved reference.

```json
[
  {
    "id": 42,
    "personId": 101,
    "editionId": 7,
    "parishId": 12,
    "shirtSize": "M",
    "guardianId": null,
    "hasCertificate": true
  }
]
```

### `POST /api/subscribe`

Creates or updates a managed profile's registration while the current edition is open for subscriptions.
Use `edition.id` for `editionId`, a `parishes[].id` for `parishId`, and a value from `shirtSizes` for `shirtSize` in the `GET /api/event` response.
`certificateUri` and `guardianId` are optional.
If supplied, `guardianId` must identify a different profile with the signed-in user's verified email as its direct email.
Omitting either field during an update retains the previously saved value.
A repeated request for the same profile and edition returns the same registration ID.
The response contains one registration object, with the same fields as a list item from `POST /api/subscriptions`.

```sh
curl 'https://staff.example.invalid/api/subscribe' \
  -H 'App-Bearer: REPLACE_WITH_APP_TOKEN' \
  --data-urlencode 'email=participant1@example.invalid' \
  --data-urlencode 'personId=101' \
  --data-urlencode 'editionId=7' \
  --data-urlencode 'parishId=12' \
  --data-urlencode 'shirtSize=M' \
  --data-urlencode 'certificateUri=app:///certificati/2026_789def.pdf'
```
