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

        // Get related posts (exclude current)
        $relatedBlogs = collect($blogs)->filter(function ($item) use ($slug) {
            return $item['slug'] !== $slug;
        })->take(2)->values()->all();

        return view('blog-detail', compact('blog', 'relatedBlogs'));
    }
}
