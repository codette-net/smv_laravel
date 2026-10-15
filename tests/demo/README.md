# SMV public teaser

Install the Playwright browser once after installing the npm dependencies:

```shell
npx playwright install chromium
```

Record the public 30–40 second teaser with:

```shell
npm run demo:teaser
```

The script uses `http://smv_laravel.test` by default. To record another public environment:

```shell
$env:SMV_DEMO_BASE_URL = 'https://staging.example.test'
npm run demo:teaser
```

The predictable output is `storage/demo/smv-platform-teaser.webm`. Demo artifacts are gitignored.

The recording stays public, uses semantic discovery rather than database IDs, and does not log in, save content, apply, or enter an administration page.

When a system FFmpeg installation is available, create an optional broadly compatible MP4 with:

```shell
ffmpeg -i storage/demo/smv-platform-teaser.webm -c:v libx264 -pix_fmt yuv420p -movflags +faststart -an storage/demo/smv-platform-teaser.mp4
```
