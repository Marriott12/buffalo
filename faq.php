<?php
/**
 * Buffalo Marathon 2025 - FAQ Page
 * Production Ready - 2025-08-08 13:41:45 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';

$faqs = [
    [
        'category' => 'Registration',
        'questions' => [
            [
                'question' => 'How do I register for Buffalo Marathon 2025?',
                'answer' => 'You can register online by creating an account on our website and selecting your preferred race category. Registration is open until September 30, 2025, or until categories are full.'
            ],
            [
                'question' => 'What is the registration deadline?',
                'answer' => 'Registration closes on September 30, 2025, at 11:59 PM. However, some categories may fill up before this date, so we recommend registering early.'
            ],
            [
                'question' => 'Can I change my race category after registration?',
                'answer' => 'Category changes are allowed up to 7 days before the event, subject to availability. Please contact our support team to make changes.'
            ],
            [
                'question' => 'Is there an age limit for participants?',
                'answer' => 'Different categories have different age requirements. The Kid Run is for ages 5-17, most adult categories are 18+, while the Family Fun Run accepts participants from age 12 and above.'
            ],
            [
                'question' => 'What payment methods do you accept?',
                'answer' => 'We accept Mobile Money (MTN, Airtel, Zamtel), Bank Transfer, and Cash payments. Mobile money and bank transfer require a reference number during registration.'
            ]
        ]
    ],
    [
        'category' => 'Race Day',
        'questions' => [
            [
                'question' => 'What time does the marathon start?',
                'answer' => 'The Full Marathon and Half Marathon start at 7:00 AM. Other categories have staggered start times: Power Challenge (10K) at 7:15 AM, Family Fun Run (5K) at 7:30 AM, and Kid Run at 8:00 AM.'
            ],
            [
                'question' => 'Where do I collect my race pack?',
                'answer' => 'Race pack collection is available on Friday, October 10, from 2:00 PM to 8:00 PM at Buffalo Park Recreation Centre, or on race morning from 5:30 AM to 6:30 AM at the Main Registration Tent.'
            ],
            [
                'question' => 'What should I bring on race day?',
                'answer' => 'Bring your race number bib, comfortable running shoes, weather-appropriate clothing, personal water bottle, and identification. Don\'t forget your registration confirmation!'
            ],
            [
                'question' => 'Will there be water stations during the race?',
                'answer' => 'Yes, water stations are strategically placed throughout all race routes. Additionally, all participants receive a free drink voucher for post-race refreshments.'
            ],
            [
                'question' => 'What happens if it rains on race day?',
                'answer' => 'The marathon will proceed rain or shine. In case of severe weather conditions that pose safety risks, we will communicate any changes via email and our website.'
            ]
        ]
    ],
    [
        'category' => 'Categories & Pricing',
        'questions' => [
            [
                'question' => 'What race categories are available?',
                'answer' => 'We offer 6 categories: Full Marathon (42K), Half Marathon (21K), Power Challenge (10K), Family Fun Run (5K), VIP Run (any distance), and Kid Run (1K). Each category is designed for different fitness levels and age groups.'
            ],
            [
                'question' => 'How much does registration cost?',
                'answer' => 'Most categories cost K500, except VIP Run (K600) and Kid Run (K450). Early bird pricing is available until August 31, 2025.'
            ],
            [
                'question' => 'What\'s included in my registration fee?',
                'answer' => 'Your registration includes a premium t-shirt, finisher\'s medal, race number with timing chip, free drink voucher, access to all event activities, pre/post-race aerobics, live entertainment, and food zones.'
            ],
            [
                'question' => 'What makes the VIP Run special?',
                'answer' => 'VIP Run participants enjoy exclusive perks including VIP tent access, premium refreshments, priority race pack collection, and special VIP areas throughout the event.'
            ],
            [
                'question' => 'Are there participant limits for each category?',
                'answer' => 'Yes, most categories have participant limits to ensure a quality experience. Full Marathon (300), Half Marathon (400), Power Challenge (500), Family Fun Run (600), VIP Run (100), and Kid Run (200).'
            ]
        ]
    ],
    [
        'category' => 'Training & Preparation',
        'questions' => [
            [
                'question' => 'Do you provide training plans?',
                'answer' => 'Yes! We offer comprehensive training guides for all race categories on our website. Whether you\'re a beginner or experienced runner, we have expert advice to help you prepare.'
            ],
            [
                'question' => 'What should I eat before the race?',
                'answer' => 'Eat a familiar, carb-rich breakfast 2-3 hours before the race. Avoid trying new foods on race day. Good options include oatmeal, bananas, or toast with honey.'
            ],
            [
                'question' => 'How should I prepare the night before?',
                'answer' => 'Get 7-8 hours of sleep, eat a carb-rich dinner, prepare your race kit, set multiple alarms, and stay well hydrated. Avoid alcohol and late-night activities.'
            ],
            [
                'question' => 'What should I wear on race day?',
                'answer' => 'Wear comfortable, well-tested running shoes and moisture-wicking clothing. Dress appropriately for the weather and avoid wearing anything new on race day.'
            ]
        ]
    ],
    [
        'category' => 'Event Information',
        'questions' => [
            [
                'question' => 'Where is the marathon taking place?',
                'answer' => 'Buffalo Marathon 2025 takes place at Buffalo Park Recreation Centre, located on Chalala-Along Joe Chibangu Road in Lusaka, Zambia.'
            ],
            [
                'question' => 'Is parking available at the venue?',
                'answer' => 'Yes, free parking is available at Buffalo Park main parking area, adjacent school grounds, and limited street parking. We also arrange special shuttle services for early morning participants.'
            ],
            [
                'question' => 'What entertainment will be available?',
                'answer' => 'Enjoy live performances by the Zambia Army Pop Band, special guest artists, pre and post-race aerobics, food zones with braai packs, chill lounge, and kids zone activities.'
            ],
            [
                'question' => 'Will there be food and drinks available?',
                'answer' => 'Yes! All participants receive a free drink voucher. Additional food vendors will be on-site offering braai packs, local cuisine, and refreshments for purchase.'
            ],
            [
                'question' => 'How will results be tracked?',
                'answer' => 'All race numbers include integrated timing chips for accurate results tracking. Results will be available on our website shortly after the race and you\'ll receive a digital completion certificate.'
            ]
        ]
    ],
    [
        'category' => 'Support',
        'questions' => [
            [
                'question' => 'How can I contact customer support?',
                'answer' => 'You can reach our support team via email at info@buffalo-marathon.com, through our contact form on the website, or call our support line during business hours.'
            ],
            [
                'question' => 'Can I get a refund if I can\'t participate?',
                'answer' => 'Refunds are available up to 14 days before the event, minus a small processing fee. Please refer to our refund policy for complete terms and conditions.'
            ],
            [
                'question' => 'What if I have dietary restrictions or medical conditions?',
                'answer' => 'Please indicate any dietary restrictions or medical conditions during registration. Our medical team will be on-site, and we\'ll accommodate dietary needs where possible.'
            ],
            [
                'question' => 'Is the event suitable for people with disabilities?',
                'answer' => 'Yes, we welcome participants with disabilities and will make reasonable accommodations. Please contact us during registration to discuss your specific needs.'
            ],
            [
                'question' => 'Can I volunteer at the event?',
                'answer' => 'Absolutely! We welcome volunteers and offer various opportunities to help make the event successful. Contact us through our volunteer program for more information.'
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Buffalo Marathon 2025</title>
    <meta name="description" content="Frequently asked questions about Buffalo Marathon 2025. Get answers about registration, race day, categories, training, and more.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .faq-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 4rem 0;
        }
        
        .faq-category {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 1.5rem;
        }
        
        .faq-item {
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.3s ease;
        }
        
        .faq-item:last-child {
            border-bottom: none;
        }
        
        .faq-item:hover {
            background-color: #f8f9fa;
        }
        
        .faq-question {
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            padding: 1.5rem;
            font-weight: 600;
            color: var(--army-green);
            transition: all 0.3s ease;
        }
        
        .faq-question:hover {
            color: var(--army-green-dark);
        }
        
        .faq-question[aria-expanded="true"] {
            background-color: rgba(75, 83, 32, 0.1);
        }
        
        .faq-answer {
            padding: 0 1.5rem 1.5rem;
            color: #6c757d;
            line-height: 1.6;
        }
        
        .search-box {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }
        
        .text-army-green { color: var(--army-green) !important; }
        .bg-army-green { background-color: var(--army-green) !important; }
        
        .highlight {
            background-color: #fff3cd;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-army-green">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-running me-2"></i>Buffalo Marathon 2025
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/schedule.php">Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/info.php">Event Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/faq.php">FAQ</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars(getCurrentUserEmail() ?: 'User'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light ms-2 px-3" href="/register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="faq-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Frequently Asked Questions</h1>
            <p class="lead mb-4">
                Find answers to the most common questions about Buffalo Marathon 2025. 
                Can't find what you're looking for? Contact our support team.
            </p>
            
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Search FAQ..." aria-label="Search FAQ">
                        <button class="btn btn-light" type="button">
                            <i class="fas fa-search text-army-green"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Content -->
    <section class="py-5">
        <div class="container">
            <!-- Quick Stats -->
            <div class="search-box">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="h3 text-army-green fw-bold"><?php echo getDaysUntilMarathon(); ?></div>
                        <div class="text-muted">Days Until Marathon</div>
                    </div>
                    <div class="col-md-3">
                        <div class="h3 text-army-green fw-bold"><?php echo getDaysUntilDeadline(); ?></div>
                        <div class="text-muted">Days to Register</div>
                    </div>
                    <div class="col-md-3">
                        <div class="h3 text-army-green fw-bold">6</div>
                        <div class="text-muted">Race Categories</div>
                    </div>
                    <div class="col-md-3">
                        <div class="h3 text-army-green fw-bold">K450-600</div>
                        <div class="text-muted">Registration Fee</div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Categories -->
            <?php foreach ($faqs as $categoryIndex => $category): ?>
                <div class="faq-category" id="category-<?php echo $categoryIndex; ?>">
                    <div class="category-header">
                        <h3 class="mb-0">
                            <i class="fas fa-question-circle me-2"></i>
                            <?php echo htmlspecialchars($category['category']); ?>
                        </h3>
                    </div>
                    
                    <div class="accordion" id="accordion<?php echo $categoryIndex; ?>">
                        <?php foreach ($category['questions'] as $questionIndex => $faq): ?>
                            <div class="faq-item">
                                <button class="faq-question" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo $categoryIndex; ?>_<?php echo $questionIndex; ?>" 
                                        aria-expanded="false">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </button>
                                
                                <div id="collapse<?php echo $categoryIndex; ?>_<?php echo $questionIndex; ?>" 
                                     class="collapse" 
                                     data-bs-parent="#accordion<?php echo $categoryIndex; ?>">
                                    <div class="faq-answer">
                                        <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Still Need Help -->
            <div class="text-center mt-5">
                <div class="bg-light rounded-3 p-5">
                    <h3 class="text-army-green mb-3">Still Need Help?</h3>
                    <p class="text-muted mb-4">
                        Can't find the answer you're looking for? Our support team is here to help.
                    </p>
                    
                    <div class="row justify-content-center g-3">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-3x text-army-green mb-3"></i>
                                    <h5>Email Support</h5>
                                    <p class="text-muted">info@buffalo-marathon.com</p>
                                    <a href="mailto:info@buffalo-marathon.com" class="btn btn-outline-army-green">Send Email</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-comments fa-3x text-army-green mb-3"></i>
                                    <h5>Contact Form</h5>
                                    <p class="text-muted">Get personalized assistance</p>
                                    <a href="/contact.php" class="btn btn-outline-army-green">Contact Us</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-info-circle fa-3x text-army-green mb-3"></i>
                                    <h5>Event Information</h5>
                                    <p class="text-muted">Complete event details</p>
                                    <a href="/info.php" class="btn btn-outline-army-green">Learn More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Buffalo Marathon Organization. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="/contact.php" class="text-light text-decoration-none me-3">Contact Us</a>
                    <a href="/terms.php" class="text-light text-decoration-none me-3">Terms</a>
                    <a href="/privacy.php" class="text-light text-decoration-none">Privacy</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            const categories = document.querySelectorAll('.faq-category');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    // Highlight search term
                    if (searchTerm.length > 2) {
                        highlightText(item, searchTerm);
                    }
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Hide empty categories
            categories.forEach(category => {
                const visibleItems = category.querySelectorAll('.faq-item[style="display: block;"], .faq-item:not([style])');
                const hasVisibleItems = Array.from(visibleItems).some(item => item.style.display !== 'none');
                category.style.display = hasVisibleItems ? 'block' : 'none';
            });
        });
        
        function highlightText(element, searchTerm) {
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );
            
            const textNodes = [];
            let node;
            
            while (node = walker.nextNode()) {
                textNodes.push(node);
            }
            
            textNodes.forEach(textNode => {
                const text = textNode.textContent;
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                if (regex.test(text)) {
                    const highlightedText = text.replace(regex, '<span class="highlight">$1</span>');
                    const span = document.createElement('span');
                    span.innerHTML = highlightedText;
                    textNode.parentNode.replaceChild(span, textNode);
                }
            });
        }
        
        // Chevron rotation on collapse
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', function() {
                const chevron = this.querySelector('i');
                setTimeout(() => {
                    if (this.getAttribute('aria-expanded') === 'true') {
                        chevron.style.transform = 'rotate(180deg)';
                    } else {
                        chevron.style.transform = 'rotate(0deg)';
                    }
                }, 10);
            });
        });
    </script>
</body>
</html>