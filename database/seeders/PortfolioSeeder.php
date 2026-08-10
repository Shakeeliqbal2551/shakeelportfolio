<?php

namespace Database\Seeders;

use App\Models\AboutSection;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Skill;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PortfolioSeeder extends Seeder
{
    /**
     * Seed the first tenant's portfolio, extracted from the original
     * hardcoded resources/views/livewire/portfolio-page.blade.php content.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'name' => 'Shakeel Iqbal Cheema',
                'email' => 'contact@shakeeliqbal.com',
                'password' => Hash::make('password'),
            ]);

            $this->command?->info('Created seed user contact@shakeeliqbal.com with password "password".');
        }

        $portfolio = Portfolio::updateOrCreate(
            ['user_id' => $user->id],
            [
                'slug' => 'shakeel-iqbal-cheema',
                'theme' => 'default',
                'site_title' => 'Shakeel Iqbal Cheema — Senior Laravel Developer',
                'meta_description' => 'Senior Laravel Developer in Islamabad, Pakistan building SaaS, eCommerce, healthcare and HR platforms for clients across the US, UK and Netherlands.',

                'hero_badge_text' => 'Available — Booking projects now',
                'hero_subtitle' => 'Senior Laravel Developer · Islamabad, Pakistan',
                'hero_title' => 'I build Laravel web apps that make you money.',
                'hero_title_accent' => 'Laravel',
                'hero_description' => "Hi, I'm Shakeel. For 6+ years I've built SaaS, eCommerce, healthcare and HR platforms for clients in the US, UK and Netherlands. You bring the idea — I'll ship a fast, secure app your users actually love. Simple as that.",
                'hero_cta_primary_label' => 'Book a Free 30-min Call',
                'hero_cta_primary_href' => '#contact',
                'hero_cta_secondary_label' => 'See My Work',
                'hero_cta_secondary_href' => '#portfolio',
                'hero_reassurance_items' => [
                    'No obligation',
                    'Usually reply within 2 hours',
                    'Free project estimate',
                ],
                'hero_stats' => [
                    ['label' => 'Years Experience', 'value' => '6+'],
                    ['label' => 'Projects Shipped', 'value' => '20+'],
                    ['label' => 'Happy Clients', 'value' => '15+'],
                    ['label' => 'Countries Served', 'value' => '6+'],
                ],
                'trust_label' => 'Trusted by clients in',
                'trust_flags' => '🇺🇸 🇬🇧 🇳🇱 🇵🇰 🇩🇪 🇦🇪',

                'contact_email' => 'contact@shakeeliqbal.com',
                'whatsapp_number' => '+923029865526',
            ]
        );

        $this->seedAboutSection($portfolio);
        $this->seedExperiences($portfolio);
        $this->seedEducations($portfolio);
        $this->seedSkills($portfolio);
        $this->seedProjects($portfolio);
        $this->seedServices($portfolio);
        $this->seedTestimonials($portfolio);
        $this->seedPosts($portfolio);
        $this->seedSampleVisitorLogs($portfolio);
    }

    private function seedAboutSection(Portfolio $portfolio): void
    {
        AboutSection::updateOrCreate(
            ['portfolio_id' => $portfolio->id],
            [
                'heading' => 'About Me',
                'title' => 'A developer who thinks like a founder.',
                'bio' => "I don't just write code — I solve business problems. Over 6+ years I've helped startups and enterprises across four continents launch and scale platforms that make money and don't break at 2 AM. I build systems that are easy to manage, focus relentlessly on real-world outcomes, and communicate in plain language — no jargon, no surprises.",
                'info_cards' => [
                    ['label' => 'Based in', 'value' => 'Islamabad, Pakistan', 'subvalue' => 'Serving clients globally'],
                    ['label' => 'Experience', 'value' => '6+ Years', 'subvalue' => 'Senior Laravel Developer'],
                    ['label' => 'Expertise', 'value' => 'SaaS · eCommerce', 'subvalue' => 'Healthcare · HR · APIs'],
                    ['label' => 'Availability', 'value' => 'Accepting new work', 'subvalue' => 'Full-time / Contract'],
                    ['label' => 'WhatsApp', 'value' => '+92 302 9865526', 'subvalue' => null],
                    ['label' => 'Email', 'value' => 'contact@shakeeliqbal.com', 'subvalue' => null],
                ],
                'cta_heading' => 'Ready to start your project?',
                'cta_text' => 'Get a free estimate — I usually reply within 2 hours.',
                'resume_path' => "img/Shakeel's Resume.pdf",
                'profile_image_path' => null,
            ]
        );
    }

    private function seedExperiences(Portfolio $portfolio): void
    {
        $portfolio->experiences()->delete();

        $rows = [
            [
                'company' => 'Point of IT (Pvt) Ltd',
                'role' => 'Senior Laravel Developer',
                'project_name' => 'Remetric Health (US-Based Healthcare Project)',
                'date_range' => '01/2025 — 07/2025',
                'description' => 'Senior Backend Developer for a US healthcare platform. Built secure Laravel APIs, optimized complex MySQL procedures, and improved performance across patient modules.',
            ],
            [
                'company' => 'Dream Warrior Group',
                'role' => 'Team Lead & Laravel Developer',
                'project_name' => 'ARTdynamix® / Dairy Queen',
                'date_range' => '04/2024 — 12/2024',
                'description' => "At Dream Warrior Group, I worked on ARTdynamix®, a CMS tailored for performing arts organizations, where I built robust Laravel backend systems and integrated ticketing, fundraising, and SEO enhancements.\n\nI also led the Dairy Queen project, a well-known American food franchise brand, overseeing architecture, backend development, and performance optimization. From planning to successful delivery, I ensured scalable, secure, and business-focused solutions tailored to their large-scale operations.",
            ],
            [
                'company' => 'AT Tech',
                'role' => 'Laravel Developer',
                'project_name' => 'Dr. iQ',
                'date_range' => '09/2019 — 04/2024',
                'description' => 'Built telemedicine platform Dr. iQ for online consultations and prescriptions. Focused on patient data security and robust backend performance.',
            ],
        ];

        foreach ($rows as $index => $row) {
            Experience::create($row + [
                'portfolio_id' => $portfolio->id,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedEducations(Portfolio $portfolio): void
    {
        $portfolio->educations()->delete();

        $rows = [
            [
                'institution' => 'International Islamic University Islamabad',
                'degree' => 'BS in Software Engineering',
                'date_range' => '08/2014 — 01/2019',
                'description' => 'Specialized in software architecture, backend development, and databases.',
            ],
            [
                'institution' => 'Sir Syed College, Wah Cantt',
                'degree' => 'FSc (Pre-Engineering)',
                'date_range' => '2012 — 2014',
                'description' => 'Focused on Physics, Mathematics, and Computer Science.',
            ],
        ];

        foreach ($rows as $index => $row) {
            Education::create($row + [
                'portfolio_id' => $portfolio->id,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedSkills(Portfolio $portfolio): void
    {
        $portfolio->skills()->delete();

        $names = [
            'Laravel (PHP)',
            'MySQL & PostgreSQL',
            'RESTful APIs',
            'Livewire',
            'Vue.js',
            'CodeIgniter',
            'JavaScript & jQuery',
            'HTML5 & CSS3',
            'OOP & MVC',
            'Docker',
            'Git & CI/CD',
            'Stored Procedures',
            'Payment Gateways',
            'Queues & Jobs',
            'Redis & Caching',
        ];

        foreach ($names as $index => $name) {
            Skill::create([
                'portfolio_id' => $portfolio->id,
                'name' => $name,
                'category' => null,
                'icon' => null,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedProjects(Portfolio $portfolio): void
    {
        $portfolio->projects()->delete();

        $rows = [
            [
                'title' => 'Remetric Health',
                'description' => 'US healthcare platform for real-time remote patient monitoring across connected devices and clinics.',
                'details' => "Remetric Health is a Remote Patient Monitoring (RPM) platform designed to help healthcare providers track patients' vital signs and health data in real time using connected medical devices. The system enables proactive care, early detection of health issues, and improved patient outcomes, especially for chronic disease management.\n\nKey features include:\n- Remetric Health is a US-based Remote Patient Monitoring (RPM) platform\n- Designed to monitor patients' vital signs remotely using connected medical devices\n- Tracks blood pressure, heart rate, glucose, weight, temperature, and SpO2 in real time\n- Helps healthcare providers intervene early with alerts and reports based on vital data\n- Used by clinics, hospitals, and home health agencies to improve chronic care management\n- Aims to reduce hospital readmissions and ensure better long-term health outcomes\n- Integrates with EHR systems and provides custom reports for clinicians and caregivers",
                'image_path' => 'img/portfolio/remetrichealth.png',
                'tags' => ['saas', 'healthcare'],
                'external_link' => 'https://www.remetrichealth.com/',
            ],
            [
                'title' => 'ARTdynamix®',
                'description' => 'CMS built for performing arts organizations — ticketing, fundraising, and SEO in one place.',
                'details' => "ARTdynamix®, developed as a powerful website builder and content management system (CMS) for The Arts, it includes time saving tools to easily manage your marketing, and content.\n\nKey features include:\n- Pre-built themes and configurations to align with your marketing needs\n- Data sync with your ticketing and fundraising platforms\n- Classes option\n- Private board and group rooms\n- Facility Rental Promotion\n- Built in SEO to increase visibility & traffic to the site\n- Drag and drop page builder\n- Designed to make events, packages, classes and fundraising more exciting and easier to manage.",
                'image_path' => 'img/portfolio/artdynamix.png',
                'tags' => ['saas'],
                'external_link' => 'https://www.artdynamix.net/',
            ],
            [
                'title' => 'Dr. iQ',
                'description' => 'Telemedicine platform with AI triage, secure medical records, and integrated payments.',
                'details' => "A telemedicine platform that provides patients with remote consultations with qualified medical professionals.\n\nKey features include:\n- Ability for patients to book appointments with qualified medical professionals, receive a diagnosis, and get prescriptions for medication, all from the comfort of their homes.\n- An AI chatbot that assists patients in answering general health-related questions.\n- Two types of users: patients and doctors.\n- Dr-iq.com is designed to make healthcare more accessible, efficient, and convenient for patients.\n- Patients can securely upload their medical records, including X-rays and lab results, for review by their doctor.\n- Doctors can easily manage their schedules, accept or reject appointment requests, and view their patients' medical records.\n- Integrated payment system for seamless transactions.\n- Strict security protocols to ensure patient data privacy.",
                'image_path' => 'img/portfolio/dr_iq.png',
                'tags' => ['saas', 'healthcare'],
                'external_link' => null,
            ],
            [
                'title' => 'Dairy Queen',
                'description' => 'Finance management system for a major US food franchise with multi-store sales, payroll and reporting.',
                'details' => "Developed a robust Financial Management System for Dairy queen using Laravel 11, enabling efficient store operations, financial tracking, and reporting. The system includes advanced features for user role management, daily financial monitoring, and comprehensive data handling for stores and users.\n\n- Role & Permission Management: Implemented a roles and permissions module using the Spatie package for seamless role-based access control, including Manager, Assistant Manager, and Staff roles.\n- Store Module: Designed a dedicated module for managing store operations, including store profiles with location details, operating hours, and assigned managers; tracking of store performance, sales, and financial summaries; and store-specific configuration for monthly targets and bonus calculations.\n- User Module: Created a user management module with user profiles with roles and permissions, activity logs for tracking user actions, password management and secure authentication, and integration with the roles module to assign permissions dynamically.\n- Inventory Module: Built a module specifically for managing inventory with features such as item price updates for real-time accuracy.\n- Daily Envelope System: Designed a module for daily entries such as net sales, total payouts, cash over/short, register readings, food/non-food costs, refunds, and cash control.\n- Monthly Factsheet: Aggregated daily envelopes to compute bonuses and generate detailed monthly reports based on store, month, and year.\n- Reports & Analytics: Created a comprehensive reporting system including Total Sales Report, Sales Journal, Manager Bonus Report, Actual Comparison Report, Sales Summaries, Percentage Changes, YTD Reports, Sales Goal Bonuses, Price Changes, Over/Short Reports, and BOM EOM Reports.\n- Data Synchronization: Ensured accurate and real-time data synchronization across modules for streamlined operations.\n\nTo check the dashboard, you can use these credentials:\nAdmin Account: admin@yopmail.com\nPassword: password",
                'image_path' => 'img/portfolio/dairy_queen.png',
                'tags' => ['finance', 'enterprise'],
                'external_link' => 'https://dq.shakeeliqbal.com/',
            ],
            [
                'title' => 'Retbajri',
                'description' => 'Multi-vendor eCommerce marketplace connecting buyers and sellers of construction materials.',
                'details' => "Retbajri is a multi-vendor website. Users can sell and buy construction materials on this website. Retbajri consists of 3 types of users:\n- Admin\n- Seller\n- Buyer\n\nAdmin Dashboard:\n- Analytics Dashboard\n- Add Product Categories\n- Add Service Categories\n- Create Plans\n- Add Brands\n- Add Packages\n- Approve/Reject Products\n- Approve/Reject Companies\n- Block/Unblock Users\n- Add Banners\n\nSeller Dashboard:\n- Analytics Dashboard\n- Complete Profile\n- Create Company Profile\n- Account Verification by OTP\n- Add multiple Products\n- Create Ad of products according to purchased plan\n- Add Services\n- See Current Ads list\n- Create Offers\n- See Quotations\n- See all orders\n- See all plans list\n- Buy Plan for Ad posting\n- See plan history\n\nBuyer Dashboard:\n- Analytics Dashboard\n- Complete Profile\n- Account Verification by OTP\n- Sell all Plans for Quotations\n- Buyer current Quotation Plan\n- All Sent Quotations\n- Pending Quotations\n- Active Quotations\n- Pending Orders\n- Active Orders\n\nTo check the dashboard, you can use these credentials:\nAdmin Account: admin@retbajri.com | Password: password\nSeller Account: seller@retbajri.com | Password: password\nBuyer Account: buyer@retbajri.com | Password: password",
                'image_path' => 'img/portfolio/retbajri.png',
                'tags' => ['ecommerce'],
                'external_link' => 'https://retbajri.shakeeliqbal.com/',
            ],
            [
                'title' => 'KeyWord Caddy',
                'description' => 'SEO SaaS that helps writers rank higher by tracking keyword usage inside their content in real time.',
                'details' => "KeywordCaddy simplifies and accelerates the process of creating SEO content that ranks high in search engines.\n\n- User can create account and buy package/plan.\n- User can create multiple projects.\n- User can write or paste the content.\n- User can import keywords to keywordcaddy.\n- It shows the user which keywords are used and which one have not been used in your content.\n- Keyword Caddy shows how many times a keyword is being used in the content.\n- User can see the total length count of words.",
                'image_path' => 'img/portfolio/keywordcaddy.png',
                'tags' => ['saas'],
                'external_link' => 'https://keywordcaddy.com/',
                'role' => 'founder',
                'company_name' => 'KeyWord Caddy',
                'your_title' => 'Founder & Developer',
            ],
            [
                'title' => 'Qurbani Pro',
                'description' => 'Online ordering + barcode-tracked logistics system used across outlets for live order status updates.',
                'details' => "Qurbani Pro is an online qurbani ordering system and qurbani process monitoring system.\n- Users can order qurbani online by filling the form and sending payment to the company on the website or visit nearest outlet for booking.\n- Users get text messages of their order number.\n\nOutlet Dashboard:\n- Enter the booking details\n- Print POS receipt of the order\n- Check all bookings entered by the outlet\n- Reprint any POS receipt from the list\n- Generate daily bookings report\n- At Eid Day:\n  - Scan Barcode at receiving the meat basket from factory (a text message will be sent to customer to pick the order from their selected outlet)\n  - Scan Barcode at delivering the meat basket to the customer (a text message will be sent to customer that you have received your order)\n\nFinance Dashboard:\n- Enter the booking details from company (only referral/employee bookings with discounts)\n- Print POS receipt of the order\n- Verify all bookings by cross checking the payments\n- Reject any booking if the payment is not received\n- Delete any booking if the booking seems to be a fake entry\n- Generate report of total orders\n\nManager Dashboard:\n- Generate barcodes against order numbers\n- Print barcode stickers\n- Scan barcode tags on the meat baskets at the day of EID to update the status of the order:\n  - Scanning at Slaughtering\n  - Scanning at Packing Order\n  - Scanning at Loading Order\n\nAdmin Dashboard:\n- Eid day progress\n- Check progress of specific outlet orders (initialized, loaded, packed, delivered etc)\n- Hourly progress\n- Total order analytics\n- Search by specific order to check status",
                'image_path' => 'img/portfolio/qurbanipro.png',
                'tags' => ['ecommerce', 'enterprise'],
                'external_link' => 'https://qurbanipro.com/',
            ],
            [
                'title' => 'HRIS',
                'description' => 'Complete HR platform covering payroll, leave, recruitment, appraisal, and employee records.',
                'details' => "HRIS consists of the following modules:\n- Recruitment management system\n- Leave management system\n- Contract management system\n- Pay roll management system\n- Training management system\n- Disciplinary management system\n- Activity log management system\n- Appraisal management system\n- Employee management system\n- Employee profile management system\n- User management system\n- Insurance management system\n- Reports: Deployment, disciplinary, employee, training staff, employment history, appraisal",
                'image_path' => 'img/portfolio/hris.png',
                'tags' => ['enterprise'],
                'external_link' => 'https://hris.ctcorg.com/',
            ],
            [
                'title' => 'Return Profit X',
                'description' => 'Crypto trading platform for buying, selling, and managing digital-asset portfolios.',
                'details' => null,
                'image_path' => 'img/portfolio/returnprofitx.png',
                'tags' => ['saas', 'finance'],
                'external_link' => 'https://returnprofitx.shakeeliqbal.com/',
            ],
            [
                'title' => 'YoWorld Info',
                'description' => 'Gaming information portal with a live price guide and item wiki for YoWorld players.',
                'details' => null,
                'image_path' => 'img/portfolio/yoworldinfo.png',
                'tags' => ['saas'],
                'external_link' => 'https://yoworld.info/',
                'role' => 'co_founder',
                'company_name' => 'YoWorld Info',
                'your_title' => 'Co-Founder',
            ],
            [
                'title' => 'OES',
                'description' => 'Online exam SaaS for computer-based testing with question banks, timers, and auto-grading.',
                'details' => "OES is a computer-based test system that can be used to conduct computer based tests online. OES is a technology-driven way to simplify examination activities like defining exam patterns with question banks, defining exam timer, objective/subjective question sections, conducting exams using the computer or mobile devices in a paperless manner.\n\nOES works on the following steps:\n- Admin can create test\n- Create project:\n  - MCQs Marks\n  - Descriptive Marks\n  - Test Time\n  - Active/Block Review Unattempted Question\n  - Active/Block Review Attempted Question\n  - Test Start Date/Time\n  - Test End Date/Time\n  - Test Instructions\n- Create post against the project\n- Create questions in Test:\n  - Add single question (select project, select section for the test, select the question type — MCQs, Descriptive)\n  - Upload multiple questions\n  - Import questions from previous tests\n- Add candidates against the validated post\n- Admin can create users and assign them roles like examiner\n- Examiner can check and mark descriptive questions\n- Admin/Examiner can generate test/project report",
                'image_path' => 'img/portfolio/oes.png',
                'tags' => ['saas', 'enterprise'],
                'external_link' => 'http://oes.chipcosulting.org/',
            ],
        ];

        foreach ($rows as $index => $row) {
            Project::create($row + [
                'portfolio_id' => $portfolio->id,
                'featured' => false,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedServices(Portfolio $portfolio): void
    {
        $portfolio->services()->delete();

        $rows = [
            [
                'title' => 'Custom Web Applications',
                'description' => 'Tailored Laravel-based web apps that solve your unique business problems from internal dashboards to full-scale platforms.',
            ],
            [
                'title' => 'SaaS Platform Development',
                'description' => 'Launch your own SaaS product with secure multi-tenant architecture, payment integration, and scalable backend systems.',
            ],
            [
                'title' => 'eCommerce & Multi-Vendor Stores',
                'description' => 'Create fully functional online stores with product management, secure checkout, vendor management, and more.',
            ],
            [
                'title' => 'API & Backend Development',
                'description' => 'Build robust REST APIs and backend logic that power mobile apps, frontend interfaces, and integrations.',
            ],
            [
                'title' => 'Database Design & Optimization',
                'description' => 'Design efficient MySQL & PostgreSQL databases, improve performance, and manage large-scale data securely.',
            ],
            [
                'title' => 'Enterprise Systems',
                'description' => 'Experience in developing healthcare platforms, HR systems, Inventory & Finance management, and more.',
            ],
            [
                'title' => 'Performance Optimization',
                'description' => 'Improve your existing Laravel applications with speed enhancements, code refactoring, and security audits.',
            ],
        ];

        foreach ($rows as $index => $row) {
            Service::create($row + [
                'portfolio_id' => $portfolio->id,
                'icon' => null,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedTestimonials(Portfolio $portfolio): void
    {
        $portfolio->testimonials()->delete();

        $rows = [
            [
                'name' => 'Michael O’Connor',
                'role_company' => 'Tech Lead, Remetric Health (USA)',
                'quote' => 'Working with Shakeel was a game-changer for our health tech platform. He streamlined our Laravel backend and built scalable APIs that significantly boosted performance and reliability.',
            ],
            [
                'name' => 'Sarah Khan',
                'role_company' => 'Product Manager, ShopNow Hub (UK)',
                'quote' => 'Shakeel helped us develop a multi-vendor eCommerce platform from the ground up. His Laravel expertise ensured smooth functionality, payment integrations, and a solid backend structure.',
            ],
            [
                'name' => 'Imran Rafiq',
                'role_company' => 'HR Systems Lead, CHIP Consulting (Pakistan)',
                'quote' => "We collaborated on a Laravel-based HR system, and Shakeel's work on modules, API development, and database design made it a success. It's now actively used by multiple departments.",
            ],
            [
                'name' => 'Hans Dekker',
                'role_company' => 'CTO, DataSight Analytics (Netherlands)',
                'quote' => 'Shakeel rebuilt our analytics dashboard using PHP, Laravel, and MySQL. Thanks to his optimization, our report load time dropped from 45 seconds to under 5 seconds.',
            ],
            [
                'name' => 'Amanda Lopez',
                'role_company' => 'Project Manager, Dream Warrior Group (USA)',
                'quote' => 'Shakeel worked on Dairy Queen’s Laravel-based finance management system. His backend development ensured smooth handling of daily sales data, monthly revenue reports, and custom analytics dashboards. For a brand as large and trusted as Dairy Queen, accuracy and performance were critical and Shakeel delivered both.',
            ],
            [
                'name' => 'Amanda Lopez',
                'role_company' => 'Project Manager, Dream Warrior Group (USA)',
                'quote' => 'Shakeel played a key role in the Laravel development of ARTdynamix®, our CMS for performing arts organizations. His contributions helped us build a modular, customizable system used by theaters and museums across the U.S.',
            ],
            [
                'name' => 'Jasper Müller',
                'role_company' => 'Founder, CodeBridge Systems (Germany)',
                'quote' => 'Our Laravel SaaS platform had critical bugs until Shakeel stepped in. He quickly diagnosed the issues and refactored backend logic with great results.',
            ],
            [
                'name' => 'Nida Patel',
                'role_company' => 'Operations Manager, StockPilot ERP (UAE)',
                'quote' => 'We needed a backend developer for our inventory system. Shakeel developed admin dashboards, REST APIs, and ensured top-notch security and performance.',
            ],
        ];

        foreach ($rows as $index => $row) {
            Testimonial::create($row + [
                'portfolio_id' => $portfolio->id,
                'avatar_path' => null,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedPosts(Portfolio $portfolio): void
    {
        $portfolio->posts()->delete();

        $rows = [
            [
                'title' => '5 Laravel Performance Tips I Use in Production',
                'excerpt' => 'A handful of small, practical changes that consistently pay off in real Laravel apps — from eager loading to queueing slow work.',
                'body' => "<p>Performance work in Laravel rarely comes down to one big trick. It's usually a handful of small, boring changes applied consistently across a codebase. Here are five I reach for on almost every production project.</p>"
                    ."<h3>1. Eager load your relationships</h3>"
                    ."<p>The N+1 query problem is still the most common performance issue I see in Laravel apps. If you're looping over a collection and touching a relationship inside the loop, you almost certainly want <code>with()</code> or <code>load()</code> instead.</p>"
                    ."<h3>2. Cache expensive, rarely-changing queries</h3>"
                    ."<p>Not everything needs to hit the database on every request. Dashboard stats, navigation menus, and settings are good candidates for a short-lived cache.</p>"
                    ."<h3>3. Push slow work onto queues</h3>"
                    ."<p>Sending email, generating PDFs, calling third-party APIs — none of that needs to block the response. Laravel's queue system makes it easy to defer this work.</p>"
                    ."<h3>Other habits worth building</h3>"
                    ."<ul><li>Add database indexes for columns you filter or sort by</li><li>Use chunked queries when processing large tables</li><li>Profile with Laravel Debugbar or Telescope before optimizing blindly</li></ul>"
                    ."<p>None of this is exotic. It's mostly discipline — but that discipline is what keeps an app fast as it grows.</p>",
                'published_at' => now()->subWeeks(3),
            ],
            [
                'title' => 'Why I Moved From CodeIgniter to Laravel',
                'excerpt' => 'CodeIgniter taught me the fundamentals of MVC. Laravel is where I learned to build software that scales — for the codebase and the team.',
                'body' => "<p>I started my career building on CodeIgniter. It was simple, fast to learn, and got the job done for small projects. But as the projects I worked on grew larger and the teams around me grew bigger, the cracks started to show.</p>"
                    ."<h2>What CodeIgniter got right</h2>"
                    ."<p>CodeIgniter taught me the fundamentals: routing, MVC separation, working directly with a query builder. There's real value in understanding those building blocks without a framework doing everything for you.</p>"
                    ."<h2>Where it started to hurt</h2>"
                    ."<p>No built-in ORM worth relying on, weak testing support, and a much smaller ecosystem meant I was writing — and maintaining — a lot of boilerplate by hand. Authentication, queues, and background jobs were all things I had to bolt on myself.</p>"
                    ."<h2>What Laravel changed for me</h2>"
                    ."<p>Eloquent, migrations, queues, a real testing story, and a package ecosystem that covers almost anything you'd need. More importantly, Laravel's conventions make it easier for a team to work on the same codebase without stepping on each other.</p>"
                    ."<ul><li>Faster to ship features without reinventing infrastructure</li><li>Easier onboarding for new developers who already know the conventions</li><li>A much bigger hiring pool of Laravel-familiar developers</li></ul>"
                    ."<p>I still have a soft spot for CodeIgniter — it's where I learned the basics. But for anything I build today, Laravel is the obvious choice.</p>",
                'published_at' => now()->subMonths(2),
            ],
            [
                'title' => 'Building Multi-Tenant SaaS with Laravel: Lessons Learned',
                'excerpt' => 'Notes on the tradeoffs between single-database and database-per-tenant architectures, and what actually mattered in practice.',
                'body' => "<p>This one is still a work in progress, but I wanted to get some early notes down while they're fresh.</p>"
                    ."<h3>Single database vs. database-per-tenant</h3>"
                    ."<p>Most of my multi-tenant Laravel projects have used a single database with a <code>tenant_id</code> (or similar) foreign key on every table, scoped through global query scopes. It's simpler to operate, easier to run cross-tenant reporting on, and cheaper to host.</p>"
                    ."<p>Database-per-tenant gives you stronger isolation and makes per-tenant backups/restores trivial, but migrations, connection management, and local development all get noticeably more complex.</p>"
                    ."<h3>What actually mattered</h3>"
                    ."<ul><li>Scoping every query correctly — a missed global scope is a real security risk</li><li>Designing the billing and plan-limit logic early, not bolted on later</li><li>Keeping seed/demo data realistic so bugs show up before customers hit them</li></ul>"
                    ."<p>More to come once this ships — this post will get an update once I've had it in production for a few months.</p>",
                'published_at' => null,
            ],
        ];

        foreach ($rows as $row) {
            Post::create($row + [
                'portfolio_id' => $portfolio->id,
                'slug' => Str::slug($row['title']),
                'featured_image_path' => null,
                'meta_title' => null,
                'meta_description' => null,
            ]);
        }
    }

    /**
     * Illustrative sample visitor analytics data — NOT real visitor records.
     * Only seeded so the Visitors dashboard has something meaningful to show.
     * Skipped if the table already has real/existing rows for this portfolio.
     */
    private function seedSampleVisitorLogs(Portfolio $portfolio): void
    {
        // Idempotent: skip if sample rows were already seeded on a previous run.
        if ($portfolio->visitorLogs()->where('user_agent', 'Sample seed data')->exists()) {
            return;
        }

        $rows = [
            ['country' => 'United States', 'city' => 'New York', 'region' => 'New York', 'browser' => 'Chrome', 'os' => 'Windows', 'device_type' => 'Desktop', 'duration_seconds' => 184, 'is_repeat_visitor' => false, 'days_ago' => 6],
            ['country' => 'United Kingdom', 'city' => 'London', 'region' => 'England', 'browser' => 'Safari', 'os' => 'MacOS', 'device_type' => 'Desktop', 'duration_seconds' => 342, 'is_repeat_visitor' => true, 'days_ago' => 5],
            ['country' => 'Pakistan', 'city' => 'Islamabad', 'region' => 'Islamabad Capital Territory', 'browser' => 'Chrome', 'os' => 'Android', 'device_type' => 'Mobile', 'duration_seconds' => 96, 'is_repeat_visitor' => false, 'days_ago' => 4],
            ['country' => 'Netherlands', 'city' => 'Amsterdam', 'region' => 'North Holland', 'browser' => 'Firefox', 'os' => 'Windows', 'device_type' => 'Desktop', 'duration_seconds' => 271, 'is_repeat_visitor' => false, 'days_ago' => 2],
            ['country' => 'United Arab Emirates', 'city' => 'Dubai', 'region' => 'Dubai', 'browser' => 'Chrome', 'os' => 'iOS', 'device_type' => 'Tablet', 'duration_seconds' => null, 'is_repeat_visitor' => false, 'days_ago' => 1],
            ['country' => 'United States', 'city' => 'New York', 'region' => 'New York', 'browser' => 'Chrome', 'os' => 'Windows', 'device_type' => 'Desktop', 'duration_seconds' => 405, 'is_repeat_visitor' => true, 'days_ago' => 0],
        ];

        foreach ($rows as $index => $row) {
            VisitorLog::create([
                'portfolio_id' => $portfolio->id,
                'ip_address' => '203.0.113.'.($index + 10),
                'country' => $row['country'],
                'city' => $row['city'],
                'region' => $row['region'],
                'latitude' => '',
                'longitude' => '',
                'page_visited' => 'home',
                'user_agent' => 'Sample seed data',
                'browser' => $row['browser'],
                'os' => $row['os'],
                'device_type' => $row['device_type'],
                'screen_resolution' => '1920x1080',
                'is_repeat_visitor' => $row['is_repeat_visitor'],
                'visit_time' => now()->subDays($row['days_ago'])->subHours(random_int(0, 12)),
                'duration_seconds' => $row['duration_seconds'],
                'session_token' => Str::uuid()->toString(),
            ]);
        }
    }
}
