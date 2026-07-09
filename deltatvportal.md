# Delta TV Internal Portal — Project Context

## What This Is

A growing internal operations portal built for Delta TV. The app starts with a public-facing YouTube downloader and expands module by module into a full internal tooling suite. No existing internal systems exist at Delta TV — this is the first.

The app is built on the Laravel TALL stack (Livewire 4 + Alpine.js + Tailwind) with a Filament 5 admin panel. The public side requires no authentication. The admin panel is protected and handles internal management tasks.

---

## Access Model

### Public (no auth)
- `/download` — YouTube Downloader

### Admin Panel (`/admin`, Filament 5, auth protected)
- cookies.txt upload and management
- Future modules: Broadcast Schedule, Asset Library, Staff Directory, Noticeboard

---

## Module 1: YouTube Downloader (Public)

### Purpose
Delta TV staff were wasting time using third-party sites like Y2Mate and yt-dls which are slow, ad-heavy, and full of redirects. This replaces all of that with a clean, fast, internal tool.

### User Flow
1. Staff member visits `/download` — no login required
2. Pastes a YouTube URL
3. Selects format (mp4 or mp3) and quality
4. Clicks Download
5. A job is dispatched to the server queue
6. The Livewire component polls every few seconds for job completion
7. When done, a download link appears
8. File is served via a signed temporary route and deleted after download or after a set number of hours via a scheduled cleanup command

### How yt-dlp Is Used

`yt-dlp` is installed as a binary directly on the VPS. It is **not** called through any third-party Python API or wrapper repo. Laravel invokes it directly from within a queued job using Symfony's `Process` component (which ships with Laravel).

The command structure looks like this conceptually:

```
yt-dlp --cookies /path/to/cookies.txt -f [format] -o [output_path] [URL]
```

The job:
- Builds the yt-dlp command based on user-selected format and quality
- Passes the path to the active `cookies.txt` file
- Runs the process and captures stdout/stderr
- On success, marks a `downloads` DB record as complete and stores the file path
- On failure, marks it as failed with the error message

Downloaded files land in `storage/app/downloads/` and are served via a signed temporary URL route. A scheduled Laravel command cleans up files older than a configured number of hours.

### Why No Python Repo
The GitHub repo [youtube-downloader-api by Simatwa](https://github.com/Simatwa/youtube-downloader-api) was considered. It is a FastAPI wrapper around yt-dlp that exposes REST endpoints. It was **rejected** in favour of calling yt-dlp directly because:
- It introduces a second process and language runtime to maintain on the VPS
- It adds a network hop between Laravel and the downloader
- It provides no meaningful functionality that can't be achieved by calling yt-dlp directly via `Process`
- It is someone else's codebase — harder to debug, harder to extend

Calling yt-dlp directly from Laravel gives full control with zero extra infrastructure.

### cookies.txt

YouTube flags requests that don't originate from a real browser session. The solution is passing a `cookies.txt` file (exported from a logged-in YouTube browser session) to yt-dlp on every request.

- The cookies file is uploaded by an admin through the Filament panel
- It is stored in a private path on the server (not publicly accessible)
- yt-dlp reads it on every download job
- When cookies expire (every few weeks typically), an admin uploads a fresh one through the panel — no code changes needed
- The Filament resource for this should show the upload date so admins know how old the current cookies are

---

## Module 2 and Beyond (Planned, Not Yet Built)

These will be added to the same app one by one after the downloader is stable:

- **Broadcast Schedule Manager** — weekly grid for editors to manage show timeslots, printable
- **Asset Library** — internal store for logos, jingles, graphics, show intros with tags and search
- **Staff Directory** — contacts, departments, roles
- **Internal Noticeboard** — announcements to replace WhatsApp group chaos

All admin-facing modules live inside the Filament panel. Some may have public or semi-public Livewire views depending on the use case.

---

## Key Architectural Decisions

- Public routes bypass all auth middleware — speed and simplicity for staff
- Filament panel handles all admin work — no custom auth UI needed
- Downloads are processed via Laravel queues — the UI never hangs waiting on yt-dlp
- Livewire polling is used for download status — no websockets needed at this stage
- Files are temporary — cleaned up automatically, not stored permanently
- cookies.txt is a server-side concern only — end users have no involvement with it
- The app is designed to grow — every new module slots in without restructuring what already exists
