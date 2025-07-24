@extends('layouts.app')

@section('title', 'FAQ - Frequently Asked Questions | AirToShare Help Center')
@section('description', 'Find answers to common questions about AirToShare file sharing. Learn about security, file limits, compatibility, and troubleshooting tips.')
@section('keywords', 'AirToShare FAQ, file sharing help, troubleshooting, file sharing questions, local network sharing help')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is AirToShare safe to use?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, AirToShare is completely safe. Your files never leave your local Wi-Fi network and are not uploaded to any external servers. All sharing happens directly between devices on the same network."
      }
    },
    {
      "@type": "Question",
      "name": "What file types does AirToShare support?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "AirToShare supports images (JPEG, PNG, GIF, WebP, SVG), documents (PDF, DOC, DOCX), text files, and archives (ZIP, RAR). Each file can be up to 10MB in size."
      }
    },
    {
      "@type": "Question",
      "name": "How many files can I share at once?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "You can share up to 20 files per session, with each file being up to 10MB in size. This limit helps ensure optimal performance for all users."
      }
    },
    {
      "@type": "Question",
      "name": "How long do shared files stay available?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Shared files automatically expire after 6 hours or after 1 hour of inactivity, whichever comes first. This ensures your privacy and helps manage storage space."
      }
    },
    {
      "@type": "Question",
      "name": "Do I need to create an account for AirToShare?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No account is required! AirToShare works instantly without any registration. Just connect to the same Wi-Fi network and start sharing."
      }
    }
  ]
}
</script>
@endsection

@section('content')

    <div class="faq-hero">
        <h1 class="faq-title">
            <i class="fas fa-question-circle"></i>
            Frequently Asked Questions
        </h1>
        <p class="faq-subtitle">
            Find quick answers to common questions about AirToShare.
            Can't find what you're looking for? Contact us for help!
        </p>
    </div>

    <div class="faq-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" id="searchInput" placeholder="Search for answers...">
    </div>

    <div class="faq-categories">
        <div class="category-card active" data-category="all">
            <div class="category-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="category-title">All Questions</div>
            <div class="category-count">15 questions</div>
        </div>

        <div class="category-card" data-category="security">
            <div class="category-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="category-title">Security & Privacy</div>
            <div class="category-count">4 questions</div>
        </div>

        <div class="category-card" data-category="usage">
            <div class="category-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <div class="category-title">Usage & Features</div>
            <div class="category-count">6 questions</div>
        </div>

        <div class="category-card" data-category="technical">
            <div class="category-icon">
                <i class="fas fa-tools"></i>
            </div>
            <div class="category-title">Technical Issues</div>
            <div class="category-count">5 questions</div>
        </div>
    </div>

    <div class="modern-card">
        <div class="faq-container">
            <!-- Security & Privacy Questions -->
            <div class="faq-item" data-category="security">
                <button class="faq-question">
                    <span>Is AirToShare safe to use?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Yes, AirToShare is completely safe to use. Your files never leave your local Wi-Fi network and are not uploaded to any external servers. All sharing happens directly between devices on the same network.</p>
                    <p>Key security features:</p>
                    <ul>
                        <li>Files stay on your local network only</li>
                        <li>No external server uploads</li>
                        <li>Automatic content expiration</li>
                        <li>No personal data collection</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button class="faq-question">
                    <span>Can other people on the same Wi-Fi see my files?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Files are associated with your specific IP address. Only devices using the same IP address can access the shared content. However, if multiple devices share the same public IP (common in home networks), they may be able to access each other's content.</p>
                    <p>For maximum privacy, use AirToShare on trusted networks only.</p>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button class="faq-question">
                    <span>How long do my files stay on the server?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Files automatically expire and are deleted under these conditions:</p>
                    <ul>
                        <li>After 6 hours from upload time</li>
                        <li>After 1 hour of inactivity (no access)</li>
                        <li>When you manually delete them</li>
                    </ul>
                    <p>This automatic cleanup ensures your privacy and helps manage storage space.</p>
                </div>
            </div>

            <div class="faq-item" data-category="security">
                <button class="faq-question">
                    <span>Do you collect any personal information?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>No, we don't collect any personal information. AirToShare doesn't require accounts, emails, or any personal details. We only temporarily store your IP address to associate files with your session, and this is automatically deleted when content expires.</p>
                </div>
            </div>

            <!-- Usage & Features Questions -->
            <div class="faq-item" data-category="usage">
                <button class="faq-question">
                    <span>What file types does AirToShare support?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>AirToShare supports a wide range of file types:</p>
                    <ul>
                        <li><strong>Images:</strong> JPEG, PNG, GIF, WebP, SVG</li>
                        <li><strong>Documents:</strong> PDF, DOC, DOCX</li>
                        <li><strong>Text:</strong> TXT files</li>
                        <li><strong>Archives:</strong> ZIP, RAR</li>
                    </ul>
                    <p>Each file can be up to 10MB in size, and you can upload up to 20 files per session.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button class="faq-question">
                    <span>How many files can I share at once?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>You can share up to 20 files per session, with each file being up to 10MB in size. This limit helps ensure optimal performance for all users on the network.</p>
                    <p>If you need to share more files, you can:</p>
                    <ul>
                        <li>Create a ZIP archive of multiple files</li>
                        <li>Share files in multiple sessions</li>
                        <li>Delete old files to make room for new ones</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button class="faq-question">
                    <span>Can I download multiple files at once?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Yes! You can select multiple files using the checkboxes and download them as a ZIP archive. This feature makes it easy to download several files in one go.</p>
                    <p>To download multiple files:</p>
                    <ul>
                        <li>Check the boxes next to files you want</li>
                        <li>Click the "Download" button</li>
                        <li>A ZIP file will be created and downloaded automatically</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button class="faq-question">
                    <span>Can I send files via email using AirToShare?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Yes, you can email files directly from AirToShare. Select the files you want to send, click the "Email" button, and fill in the recipient's email address along with a custom message.</p>
                    <p>The files will be sent as email attachments, making it easy to share with people not on your network.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button class="faq-question">
                    <span>Do I need to create an account for AirToShare?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>No account is required! AirToShare works instantly without any registration. Just connect to the same Wi-Fi network and start sharing. This makes it perfect for quick, hassle-free file sharing.</p>
                </div>
            </div>

            <div class="faq-item" data-category="usage">
                <button class="faq-question">
                    <span>Can I share text as well as files with AirToShare?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Absolutely! AirToShare has a dedicated text sharing feature. You can paste up to 50,000 characters of text, and it will automatically detect and make URLs clickable.</p>
                    <p>Text features include:</p>
                    <ul>
                        <li>Character counter</li>
                        <li>Automatic link detection</li>
                        <li>Copy to clipboard functionality</li>
                        <li>Same expiration rules as files</li>
                    </ul>
                </div>
            </div>

            <!-- Technical Issues Questions -->
            <div class="faq-item" data-category="technical">
                <button class="faq-question">
                    <span>What devices and browsers does AirToShare support?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>AirToShare works on any device with a modern web browser, including:</p>
                    <ul>
                        <li><strong>Desktop:</strong> Windows, Mac, Linux</li>
                        <li><strong>Mobile:</strong> iOS (Safari), Android (Chrome)</li>
                        <li><strong>Browsers:</strong> Chrome, Firefox, Safari, Edge</li>
                    </ul>
                    <p>No apps or software installation required - just open your web browser!</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button class="faq-question">
                    <span>Why can't I see files from another device?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Make sure both devices are:</p>
                    <ul>
                        <li>Connected to the same Wi-Fi network</li>
                        <li>Using the same IP address (common in home networks)</li>
                        <li>Accessing the same AirToShare URL</li>
                    </ul>
                    <p>If you're still having issues, try refreshing the page or checking your network connection.</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button class="faq-question">
                    <span>Upload failed - what should I do?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>If your upload fails, check these common issues:</p>
                    <ul>
                        <li>File size exceeds 10MB limit</li>
                        <li>Unsupported file type</li>
                        <li>You've reached the 20 file limit</li>
                        <li>Poor network connection</li>
                        <li>Browser storage issues</li>
                    </ul>
                    <p>Try refreshing the page and uploading again. If problems persist, try a different browser.</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button class="faq-question">
                    <span>The page is loading slowly - why?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Slow loading can be caused by:</p>
                    <ul>
                        <li>Weak Wi-Fi signal</li>
                        <li>Network congestion</li>
                        <li>Large files being processed</li>
                        <li>Browser cache issues</li>
                    </ul>
                    <p>Try moving closer to your Wi-Fi router, clearing browser cache, or refreshing the page.</p>
                </div>
            </div>

            <div class="faq-item" data-category="technical">
                <button class="faq-question">
                    <span>Can I use AirToShare on mobile data?</span>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>While AirToShare works on mobile data, it's designed for local Wi-Fi networks. Using mobile data means:</p>
                    <ul>
                        <li>Files are only accessible from that specific device</li>
                        <li>No cross-device sharing capability</li>
                        <li>Uses your mobile data allowance</li>
                    </ul>
                    <p>For the best experience and true cross-device sharing, use Wi-Fi.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="contact-section">
        <h2 class="contact-title">
            <i class="fas fa-headset"></i>
            Still Need Help?
        </h2>
        <p class="contact-text">
            Can't find the answer you're looking for? Our support team is here to help you get the most out of AirToShare.
        </p>
        <a href="{{ url('/feedback') }}" class="contact-button">
            <i class="fas fa-envelope"></i>
            Contact Support
        </a>
    </div>

    <script>
        $(document).ready(function() {
            setupFAQ();
        });

        function setupFAQ() {
            // FAQ accordion functionality
            $('.faq-question').click(function() {
                const $this = $(this);
                const answer = $this.next('.faq-answer');
                const icon = $this.find('.faq-icon');

                // Close other open FAQs
                $('.faq-question').not($this).removeClass('active');
                $('.faq-answer').not(answer).removeClass('show').slideUp(300);
                $('.faq-icon').not(icon).css('transform', 'rotate(0deg)');

                // Toggle current FAQ
                if ($this.hasClass('active')) {
                    $this.removeClass('active');
                    answer.removeClass('show').slideUp(300);
                } else {
                    $this.addClass('active');
                    answer.addClass('show').slideDown(300);
                }
            });

            // Category filtering
            $('.category-card').click(function() {
                const category = $(this).data('category');

                $('.category-card').removeClass('active');
                $(this).addClass('active');

                if (category === 'all') {
                    $('.faq-item').show();
                } else {
                    $('.faq-item').hide();
                    $(`.faq-item[data-category="${category}"]`).show();
                }

                // Close all open FAQs when switching categories
                $('.faq-question').removeClass('active');
                $('.faq-answer').removeClass('show').slideUp(300);
            });

            // Search functionality
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();

                $('.faq-item').each(function() {
                    const question = $(this).find('.faq-question span').text().toLowerCase();
                    const answer = $(this).find('.faq-answer').text().toLowerCase();

                    if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                        $(this).show();

                        // Highlight search terms
                        if (searchTerm.length > 2) {
                            highlightSearchTerm($(this), searchTerm);
                        }
                    } else {
                        $(this).hide();
                    }
                });

                // Show all categories when searching
                if (searchTerm.length > 0) {
                    $('.category-card').removeClass('active');
                    $('.category-card[data-category="all"]').addClass('active');
                }
            });
        }

        function highlightSearchTerm(element, term) {
            const question = element.find('.faq-question span');
            const answer = element.find('.faq-answer');

            // Remove previous highlights
            question.html(question.html().replace(/<span class="highlight">(.*?)<\/span>/gi, '$1'));
            answer.html(answer.html().replace(/<span class="highlight">(.*?)<\/span>/gi, '$1'));

            // Add new highlights
            const regex = new RegExp(`(${term})`, 'gi');
            question.html(question.html().replace(regex, '<span class="highlight">$1</span>'));
            answer.html(answer.html().replace(regex, '<span class="highlight">$1</span>'));
        }
    </script>
@endsection
