<?php

namespace App\Http\Controllers;

class BlogController extends Controller
{
    /**
     * Static blog data - no database needed
     */
    /**
     * Static blog data - no database needed
     */
    public static function getBlogs()
    {
        return [
            [
                'slug' => 'user-accounts-and-email-verification',
                'title' => 'User Accounts & Email Verification Are Here',
                'excerpt' => 'Create a free account to unlock higher limits, save your shares, and verify your email with a secure one-click link.',
                'image' => '/assets/images/blog/accounts-email-verification.png',
                'date' => 'June 21, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '4 min read',
                'content' => '
                    <p>AirToShare now supports <strong>optional user accounts</strong>. You can still share instantly as a guest, but creating an account unlocks more storage, longer expiry options, and a personal dashboard.</p>

                    <h2>Why create an account?</h2>
                    <ul>
                        <li><strong>100 files</strong> and <strong>1 GB</strong> storage (vs 50 files for guests)</li>
                        <li><strong>My Shares</strong> dashboard to manage everything in one place</li>
                        <li><strong>Favourites</strong> — pin up to 50 important shares</li>
                        <li><strong>30-day expiry</strong> option for longer-lived shares</li>
                    </ul>

                    <h2>Email verification</h2>
                    <p>When you register, we send a <strong>Verify Email Address</strong> link to your inbox. Click it to activate your account and access My Shares. The link is signed and expires after 60 minutes — use <strong>Resend verification email</strong> if you need a new one.</p>

                    <h2>Getting started</h2>
                    <ol>
                        <li>Click <strong>Register</strong> in the header</li>
                        <li>Enter your email and password (8+ characters)</li>
                        <li>Open the verification email and click the link</li>
                        <li>Visit <strong>My Shares</strong> to see content saved while logged in</li>
                    </ol>

                    <p>Already uploaded files as a guest? Log in on the same site URL — your recent guest content is linked to your account automatically.</p>
                ',
            ],
            [
                'slug' => 'my-shares-dashboard-and-favourites',
                'title' => 'My Shares Dashboard & Favourites',
                'excerpt' => 'Manage all your shares from one page. Star important ones as favourites and open, copy, or publish them in seconds.',
                'image' => '/assets/images/blog/my-shares-favourites.png',
                'date' => 'June 20, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '3 min read',
                'content' => '
                    <p>Logged-in users now get a dedicated <strong>My Shares</strong> page at <code>/account/shares</code> — your command centre for everything you have shared.</p>

                    <h2>What you will see</h2>
                    <ul>
                        <li>Active share count and favourite usage (up to <strong>50</strong>)</li>
                        <li>Each share as a card with expiry, file count, and badges (E2EE, password, public)</li>
                        <li>Quick actions: <strong>Open share</strong>, enable/disable public gallery</li>
                    </ul>

                    <h2>Favourites</h2>
                    <p>Click the <strong>star icon</strong> on any share card to add it to favourites. Favourited shares show a gold badge so you can find important content fast. Click the star again to remove.</p>

                    <h2>Tip</h2>
                    <p>Upload files and save text while <strong>logged in</strong> on the home page — they appear in My Shares automatically. Use the same browser URL you used to register (e.g. <code>dev.fileshare.test</code>, not a different localhost port).</p>
                ',
            ],
            [
                'slug' => 'rooms-share-with-a-code',
                'title' => 'Rooms: Share With a 6-Character Code',
                'excerpt' => 'Create a Room and let others join with a short code — perfect for meetings, classrooms, and quick team hand-offs.',
                'image' => '/assets/images/blog/rooms-share-code.png',
                'date' => 'June 19, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '3 min read',
                'content' => '
                    <p><strong>Rooms</strong> let multiple people access the same share session using a simple <strong>6-character code</strong> — no long URLs to dictate over a call.</p>

                    <h2>Create a Room</h2>
                    <ol>
                        <li>Click <strong>Create Room</strong> on the home page</li>
                        <li>Choose optional expiry and password protection</li>
                        <li>Share the code or link (<code>/r/ABC123</code>) with others on your network</li>
                    </ol>

                    <h2>Join a Room</h2>
                    <p>Click <strong>Join Room</strong>, enter the code (letters auto-uppercase), and press Enter. Password-protected rooms ask for the room password first.</p>

                    <h2>Clipboard sync</h2>
                    <p>Room members can sync clipboard text in real time — great for sharing snippets, links, or notes during a session.</p>
                ',
            ],
            [
                'slug' => 'realtime-sync-with-reverb',
                'title' => 'Real-Time Sync: See Changes Instantly',
                'excerpt' => 'Text edits, new uploads, and clipboard updates appear live across devices — powered by Laravel Reverb WebSockets.',
                'image' => '/assets/images/blog/realtime-sync.png',
                'date' => 'June 18, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '3 min read',
                'content' => '
                    <p>AirToShare now pushes updates to connected browsers in <strong>real time</strong>. When someone adds a file or edits shared text, other devices on the same share or room see it without refreshing.</p>

                    <h2>What syncs live</h2>
                    <ul>
                        <li>New file uploads and deletions</li>
                        <li>Shared text changes</li>
                        <li>Room clipboard updates</li>
                        <li>Expiry reminder notifications (browser)</li>
                    </ul>

                    <h2>Under the hood</h2>
                    <p>We use <strong>Laravel Reverb</strong> for private WebSocket channels. Each share and room gets its own channel so updates only go to authorised viewers.</p>
                ',
            ],
            [
                'slug' => 'chunked-uploads-large-files',
                'title' => 'Chunked Uploads for Large Files',
                'excerpt' => 'Files over 5 MB upload in resumable chunks with integrity checks — up to 500 MB per file without timeouts.',
                'image' => '/assets/images/blog/chunked-uploads.png',
                'date' => 'June 17, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '4 min read',
                'content' => '
                    <p>Small files still upload in one request. Files <strong>larger than 5 MB</strong> automatically use our <strong>chunked upload</strong> pipeline — faster, resumable, and safer on slow connections.</p>

                    <h2>How it works</h2>
                    <ol>
                        <li>The browser splits the file into chunks</li>
                        <li>Each chunk is verified with <strong>SHA-256</strong></li>
                        <li>When all chunks arrive, the server assembles the final file</li>
                        <li>Progress and retry are handled in the upload manager</li>
                    </ol>

                    <h2>Limits</h2>
                    <ul>
                        <li>Up to <strong>500 MB</strong> per file via chunked upload</li>
                        <li>Standard uploads remain up to <strong>25 MB</strong></li>
                        <li>Account owners get higher total storage (1 GB)</li>
                    </ul>
                ',
            ],
            [
                'slug' => 'end-to-end-encryption-e2ee',
                'title' => 'Optional End-to-End Encryption (E2EE)',
                'excerpt' => 'Encrypt shared text in your browser. The server never sees the key — only people with the link fragment can decrypt.',
                'image' => '/assets/images/blog/e2ee-encryption.png',
                'date' => 'June 16, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Security',
                'read_time' => '5 min read',
                'content' => '
                    <p>Enable <strong>End-to-End Encryption</strong> when saving text to protect content from anyone without the decryption key — including the server.</p>

                    <h2>How E2EE works here</h2>
                    <ul>
                        <li>Your browser generates an AES-GCM key</li>
                        <li>Text is encrypted before it is sent</li>
                        <li>The key is placed in the URL <strong>fragment</strong> (<code>#k=...</code>) — never sent to the server</li>
                        <li>Share the <strong>full URL including #k=</strong> with recipients</li>
                    </ul>

                    <h2>Requirements</h2>
                    <p>E2EE needs a <strong>secure context</strong>: HTTPS or <code>http://127.0.0.1</code>. On plain HTTP hostnames (e.g. some local dev domains), the checkbox is disabled with an explanation.</p>

                    <p>If decryption fails, you will see an error — wrong key, corrupted data, or an incomplete link.</p>
                ',
            ],
            [
                'slug' => 'public-gallery-links',
                'title' => 'Public Gallery Links',
                'excerpt' => 'Turn any share into a read-only public page with a short /p/ link — great for portfolios and client deliveries.',
                'image' => '/assets/images/blog/public-gallery.png',
                'date' => 'June 15, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '3 min read',
                'content' => '
                    <p>Account owners can enable a <strong>public gallery</strong> for any share they own. Visitors get a clean page at <code>/p/your-slug</code> without needing to join a room or know your IP.</p>

                    <h2>Enable public access</h2>
                    <ol>
                        <li>Open <strong>My Shares</strong></li>
                        <li>Find the share and click <strong>Enable public</strong></li>
                        <li>Copy the public URL and share it anywhere</li>
                    </ol>

                    <h2>Disable anytime</h2>
                    <p>Click <strong>Disable public</strong> on the share card to revoke the link immediately. Password-protected shares still require the password before content is shown.</p>
                ',
            ],
            [
                'slug' => 'password-protected-shares',
                'title' => 'Password-Protected Shares',
                'excerpt' => 'Add a password to any share or room so only people with the secret can view files and text.',
                'image' => '/assets/images/blog/password-protected.png',
                'date' => 'June 14, 2026',
                'author' => 'AirToShare Team',
                'category' => 'Security',
                'read_time' => '3 min read',
                'content' => '
                    <p>Protect sensitive shares with an optional <strong>password</strong> (6–128 characters). The password is stored as a bcrypt hash — we never keep the plain text.</p>

                    <h2>When viewers open the link</h2>
                    <p>They see a password prompt first. Wrong passwords return a generic error (no hint whether the share exists). After five failed attempts from the same IP within 15 minutes, verification is temporarily rate-limited.</p>

                    <h2>Works with</h2>
                    <ul>
                        <li>Share view pages (<code>/s/...</code>)</li>
                        <li>Public gallery links (when enabled)</li>
                        <li>Password-protected Rooms</li>
                        <li>Real-time channels (subscription requires prior password success)</li>
                    </ul>
                ',
            ],
            [
                'slug' => 'instant-file-previews',
                'title' => 'Instant File Previews',
                'excerpt' => 'Preview images, PDFs, and videos inline or fullscreen — lazy-loaded for speed with a dedicated modal viewer.',
                'image' => '/assets/images/blog/file-previews.png',
                'date' => 'June 13, 2026',
                'author' => 'AirToShare Team',
                'category' => 'UX Update',
                'read_time' => '3 min read',
                'content' => '
                    <p>AirToShare now renders rich <strong>file previews</strong> without downloading first.</p>

                    <h2>Inline previews</h2>
                    <p>The preview renderer lazy-loads content when file rows scroll into view:</p>
                    <ul>
                        <li><strong>Images</strong> up to 25 MB</li>
                        <li><strong>PDFs</strong> via PDF.js up to 25 MB</li>
                        <li><strong>Videos</strong> up to 200 MB</li>
                    </ul>

                    <h2>Fullscreen modal</h2>
                    <p>Click any file thumbnail to open the fullscreen preview modal — navigate between files, download, or share from there.</p>

                    <h2>Performance</h2>
                    <p>Previews unload after 5 seconds out of view to save memory. Failed loads show a retry button; download always remains available.</p>
                ',
            ],
            [
                'slug' => 'introducing-airtoshare',
                'title' => 'Introducing AirToShare: The Future of File Sharing',
                'excerpt' => 'Experience the fastest, most secure way to share files between devices. No cloud uploads, just instant peer-to-peer transfer for your essential files.',
                'image' => '/assets/images/blog/airtoshare-app.png',
                'date' => 'March 21, 2025',
                'author' => 'AirToShare Team',
                'category' => 'Announcement',
                'read_time' => '5 min read',
                'content' => '
                    <p>We are thrilled to introduce <strong>AirToShare</strong>, a revolutionary new platform designed to make file sharing as simple and seamless as possible. In a world where we constantly switch between phones, laptops, and tablets, moving files shouldn\'t be a hassle.</p>

                    <h2>The Problem with Traditional Sharing</h2>
                    <p>We\'ve all been there: emailing files to ourselves, waiting for cloud uploads, or hunting for a USB drive. These methods are slow and often insecure.</p>

                    <h2>The AirToShare Solution</h2>
                    <p>AirToShare solves these problems by using <strong>local network technology</strong>. This means:</p>
                    <ul>
                        <li><strong>Blazing Fast Speeds:</strong> Files transfer directly between devices over your Wi-Fi, not through the slow internet.</li>
                        <li><strong>Instant Sharing:</strong> Share photos, documents, and small videos instantly (up to 25MB).</li>
                        <li><strong>Secure & Private:</strong> Files are stored temporarily on our secure local disk and automatically deleted after 24 hours, ensuring your privacy.</li>
                        <li><strong>Cross-Platform Freedom:</strong> Works perfectly on Windows, macOS, Android, iOS, and Linux.</li>
                    </ul>

                    <h2>Key Features at a Glance</h2>
                    <ul>
                        <li><strong>One-Time Downloads:</strong> Share sensitive info securely with links that self-destruct after use.</li>
                        <li><strong>Device Nicknames:</strong> Give your gadgets friendly names like "My iPhone" instead of confusing IP addresses.</li>
                        <li><strong>QR Code Connect:</strong> Scan to connect instantly—no typing required.</li>
                        <li><strong>Dark Mode:</strong> A beautiful, eye-strain-free interface for night owls.</li>
                    </ul>

                    <p>This is just the beginning. We have an exciting roadmap ahead with features like folders, rooms, and more. Thank you for joining us on this journey!</p>

                    <p>Start sharing today at <a href="/">airtoshare.app</a></p>
                ',
            ],
            [
                'slug' => 'dark-mode-now-available',
                'title' => 'AirToShare Now Supports Dark Mode!',
                'excerpt' => 'Give your eyes a break with our new dark mode feature. Toggle between light and dark themes for comfortable viewing in any lighting condition.',
                'image' => '/assets/images/blog/dark-mode.png',
                'date' => 'January 20, 2025',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '3 min read',
                'content' => '
                    <p>We\'re excited to announce that <strong>Dark Mode</strong> is now available in AirToShare! This has been one of our most requested features, and we\'re thrilled to finally bring it to you.</p>

                    <h2>Why Dark Mode?</h2>
                    <p>Dark mode isn\'t just about aesthetics – it\'s about comfort and accessibility. Here\'s why you\'ll love it:</p>
                    <ul>
                        <li><strong>Reduced Eye Strain:</strong> Perfect for late-night file sharing sessions</li>
                        <li><strong>Battery Savings:</strong> OLED screens use less power with dark themes</li>
                        <li><strong>Better Focus:</strong> Less visual noise means more focus on your content</li>
                        <li><strong>Modern Look:</strong> A sleek, professional appearance</li>
                    </ul>

                    <h2>How to Enable Dark Mode</h2>
                    <p>Enabling dark mode is super easy:</p>
                    <ol>
                        <li>Look for the <strong>moon icon</strong> at the bottom right of your screen</li>
                        <li>Click once to switch to dark mode</li>
                        <li>Click again to return to light mode</li>
                    </ol>
                    <p>Your preference is automatically saved, so when you return to AirToShare, it will remember your choice!</p>

                    <h2>What\'s Included</h2>
                    <p>Our dark mode is comprehensive – every element has been carefully designed:</p>
                    <ul>
                        <li>Navigation bar with dark background</li>
                        <li>All cards and panels</li>
                        <li>Modals and popups</li>
                        <li>Input fields and buttons</li>
                        <li>Footer and all pages</li>
                    </ul>

                    <p>We\'ve used a beautiful blue-purple gradient that looks stunning in both modes. Try it out and let us know what you think!</p>
                ',
            ],
            [
                'slug' => 'quick-connect-qr-codes',
                'title' => 'Quick Connect with QR Codes',
                'excerpt' => 'Connect devices instantly by scanning a QR code. No typing URLs, no hassle – just scan and share across all your devices.',
                'image' => '/assets/images/blog/qr-code.png',
                'date' => 'January 19, 2025',
                'author' => 'AirToShare Team',
                'category' => 'Feature Update',
                'read_time' => '2 min read',
                'content' => '
                    <p>Say goodbye to manually typing URLs! Our new <strong>QR Code Quick Connect</strong> feature makes connecting devices faster than ever.</p>

                    <h2>How It Works</h2>
                    <p>The concept is simple but powerful:</p>
                    <ol>
                        <li>Click the <strong>"Quick Connect"</strong> button in the info panel</li>
                        <li>A QR code appears on your screen</li>
                        <li>Scan it with your phone\'s camera</li>
                        <li>Instantly access your shared files!</li>
                    </ol>

                    <h2>Perfect For</h2>
                    <ul>
                        <li><strong>Office Meetings:</strong> Share your session with colleagues instantly</li>
                        <li><strong>Multiple Devices:</strong> Connect your phone, tablet, and laptop seamlessly</li>
                        <li><strong>Guest Access:</strong> Let visitors access shared files without typing</li>
                        <li><strong>Quick Transfers:</strong> Move files between devices in seconds</li>
                    </ul>

                    <h2>Copy Link Option</h2>
                    <p>Don\'t have a camera handy? No problem! The QR modal also includes a <strong>"Copy Link"</strong> button so you can share the URL via messaging apps or email.</p>

                    <p>This feature is designed for maximum convenience. Whether you\'re in a meeting, at home, or on the go – connecting is now effortless!</p>
                ',
            ],
            [
                'slug' => 'one-time-download-links',
                'title' => 'Secure One-Time Download Links',
                'excerpt' => 'Share files with maximum security. One-time links automatically delete the file after the first download – perfect for sensitive documents.',
                'image' => '/assets/images/blog/one-time.png',
                'date' => 'January 18, 2025',
                'author' => 'AirToShare Team',
                'category' => 'Security',
                'read_time' => '4 min read',
                'content' => '
                    <p>Privacy and security are at the core of AirToShare. Today, we\'re introducing <strong>One-Time Download Links</strong> – a powerful feature for sharing sensitive files.</p>

                    <h2>What Are One-Time Links?</h2>
                    <p>A one-time download link is exactly what it sounds like: a special link that works <strong>only once</strong>. After the first download, the file is automatically and permanently deleted.</p>

                    <h2>When to Use One-Time Links</h2>
                    <ul>
                        <li><strong>Sensitive Documents:</strong> Contracts, IDs, financial documents</li>
                        <li><strong>Confidential Information:</strong> Passwords, access codes, private data</li>
                        <li><strong>Limited Distribution:</strong> When you want only one person to access a file</li>
                        <li><strong>Time-Sensitive Files:</strong> Documents that shouldn\'t remain available</li>
                    </ul>

                    <h2>How to Create a One-Time Link</h2>
                    <ol>
                        <li>Upload your file to AirToShare</li>
                        <li>Click on the file to open preview</li>
                        <li>Click the <strong>green Share button</strong></li>
                        <li>Toggle ON the <strong>"One-Time Download"</strong> option</li>
                        <li>Copy the generated link</li>
                        <li>Share it with your recipient</li>
                    </ol>

                    <h2>Security Benefits</h2>
                    <ul>
                        <li>File is deleted immediately after download</li>
                        <li>Link becomes invalid after first use</li>
                        <li>No traces left on the server</li>
                        <li>Peace of mind for sensitive sharing</li>
                    </ul>

                    <p>With one-time links, you have complete control over your shared content. Share with confidence!</p>
                ',
            ],
            [
                'slug' => 'device-nicknames-personalization',
                'title' => 'Personalize Your Devices with Nicknames',
                'excerpt' => 'Give your devices friendly names for easier identification. Say goodbye to confusing IP addresses and hello to "My Laptop" or "Office PC".',
                'image' => '/assets/images/blog/device-names.png',
                'date' => 'January 17, 2025',
                'author' => 'AirToShare Team',
                'category' => 'UX Update',
                'read_time' => '2 min read',
                'content' => '
                    <p>Have you ever been confused by IP addresses when sharing files? Not anymore! Our new <strong>Device Nicknames</strong> feature lets you personalize your AirToShare experience.</p>

                    <h2>What is Device Nicknames?</h2>
                    <p>Instead of seeing a technical IP address, you can now set a friendly name for your device. It appears right in the info panel and helps you identify which device you\'re using.</p>

                    <h2>How to Set Your Device Name</h2>
                    <ol>
                        <li>Look for <strong>"My Device"</strong> in the info panel</li>
                        <li>Click on it or the pencil icon</li>
                        <li>Type your preferred name (e.g., "Bilal\'s Laptop")</li>
                        <li>Press Enter to save</li>
                    </ol>

                    <h2>Example Names</h2>
                    <ul>
                        <li>"Work PC"</li>
                        <li>"Home Office"</li>
                        <li>"iPhone Pro"</li>
                        <li>"Living Room iPad"</li>
                        <li>"Samsung Galaxy"</li>
                    </ul>

                    <h2>Persistent Across Sessions</h2>
                    <p>Your nickname is saved locally, so every time you open AirToShare on that device, it will remember your custom name. No sign-up or account needed!</p>

                    <p>This small feature makes a big difference when you\'re managing multiple devices. Give it a try and make AirToShare truly yours!</p>
                ',
            ],
        ];
    }

    /**
     * Display blog listing page
     */
    public function index()
    {
        $blogs = $this->getBlogs();

        return view('blogs', compact('blogs'));
    }

    /**
     * Display single blog post
     */
    public function show($slug)
    {
        $blogs = $this->getBlogs();
        $blog = collect($blogs)->firstWhere('slug', $slug);

        if (! $blog) {
            abort(404);
        }

        // Get related posts (same category first, exclude current)
        $relatedBlogs = collect($blogs)
            ->filter(fn ($item) => $item['slug'] !== $slug)
            ->sortByDesc(fn ($item) => (int) ($item['category'] === $blog['category']))
            ->take(3)
            ->values()
            ->all();

        return view('blog-detail', compact('blog', 'relatedBlogs'));
    }
}
