<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\ProfilePhoto;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectImage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WhyPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioSeeder extends Seeder
{
    protected string $disk = 'public';

    public function run(): void
    {
        $user      = $this->createUser();
        $portfolio = $this->createPortfolio($user);

        $this->createSiteSettings($portfolio);
        $this->copyProfilePhotos($portfolio);

        $categories = $this->createProjectCategories($portfolio);
        $this->createProjects($portfolio, $categories);

        $this->createSkills($portfolio);
        $this->createExperiences($portfolio);
        $this->createEducations($portfolio);
        $this->createServices($portfolio);
        $this->createTestimonials($portfolio);
        $this->createWhyPoints($portfolio);

        $this->createBlogStarters($portfolio, $user);
    }

    protected function createUser(): User
    {
        return User::updateOrCreate(
            ['email' => 'contact@shakeeliqbal.com'],
            [
                'name'              => 'Shakeel Iqbal Cheema',
                'username'          => 'shakeel',
                'password'          => Hash::make('password'),
                'is_admin'          => true,
                'email_verified_at' => now(),
                'timezone'          => 'Asia/Karachi',
            ],
        );
    }

    protected function createPortfolio(User $user): Portfolio
    {
        return Portfolio::updateOrCreate(
            ['slug' => 'shakeel-iqbal'],
            [
                'user_id'      => $user->id,
                'display_name' => 'Shakeel Iqbal Cheema',
                'headline'     => 'Senior Laravel Developer',
                'theme'        => 'default',
                'is_active'    => true,
                'is_primary'   => true,
            ],
        );
    }

    protected function createSiteSettings(Portfolio $portfolio): void
    {
        SiteSetting::updateOrCreate(
            ['portfolio_id' => $portfolio->id],
            [
                'hero_badge'                => 'Available — Booking projects now',
                'hero_subtitle'             => 'Senior Laravel Developer · Islamabad, Pakistan',
                'hero_title_html'           => 'I build <span class="accent">Laravel</span> web apps<br>that make you money.',
                'hero_description'          => "Hi, I'm <strong>Shakeel</strong>. For 6+ years I've built SaaS, eCommerce, healthcare and HR platforms for clients in the US, UK and Netherlands. You bring the idea — I'll ship a fast, secure app your users actually love. Simple as that.",
                'hero_cta_primary_label'    => 'Book a Free 30-min Call',
                'hero_cta_primary_url'      => '#contact',
                'hero_cta_secondary_label'  => 'See My Work',
                'hero_cta_secondary_url'    => '#portfolio',
                'hero_reassurance'          => [
                    'No obligation',
                    'Usually reply within 2 hours',
                    'Free project estimate',
                ],
                'hero_flags'                => ['🇺🇸', '🇬🇧', '🇳🇱', '🇵🇰', '🇩🇪', '🇦🇪'],
                'stat_years'                => '6+',
                'stat_projects'             => '20+',
                'stat_clients'              => '15+',
                'stat_countries'            => '6+',
                'about_subtitle'            => 'About Me',
                'about_title'               => 'A developer who thinks like a founder.',
                'about_description'         => "I don't just write code — I solve business problems. Over 6+ years I've helped startups and enterprises across <strong>four continents</strong> launch and scale platforms that make money and don't break at 2 AM. I build systems that are easy to manage, focus relentlessly on real-world outcomes, and communicate in plain language — no jargon, no surprises.",
                'about_location'            => 'Islamabad, Pakistan',
                'about_phone'               => '+92 302 9865526',
                'about_email'               => 'contact@shakeeliqbal.com',
                'about_whatsapp'            => '+923029865526',
                'about_linkedin'            => 'https://www.linkedin.com/in/shakeel-iqbal-cheema-725940168/',
                'about_resume_path'         => null,
                'contact_subtitle'          => "Let's Connect",
                'contact_title'             => 'Book a Free Consultation',
                'contact_description'       => "Let's discuss your project goals and challenges in a quick consultation. I'll review your requirements, share insights, and guide you on the best technical approach for your Laravel or web application project.",
                'contact_address'           => 'Islamabad, Pakistan',
                'seo_title'                 => 'Laravel Developer | Shakeel Iqbal Cheema - Custom Web & SaaS Solutions',
                'seo_description'           => 'Hire Shakeel Iqbal Cheema, a top Laravel developer in Pakistan with 6+ years of experience building custom web apps, SaaS platforms, eCommerce systems, and enterprise solutions.',
                'seo_keywords'              => 'Laravel Developer Pakistan, Hire Laravel Expert, Laravel Web Developer, SaaS Laravel Developer, Custom Web Application, PHP Backend Developer, eCommerce Developer Pakistan, Full Stack Laravel',
                'canonical_url'             => 'https://shakeeliqbal.com/',
            ],
        );
    }

    protected function copyProfilePhotos(Portfolio $portfolio): void
    {
        $files = [
            ['source' => public_path('img/shakeel1.png'), 'alt' => 'Shakeel Iqbal Cheema portrait'],
            ['source' => public_path('img/shakeel.jpg'),  'alt' => 'Shakeel Iqbal Cheema portrait alt'],
        ];

        $sort = 1;

        foreach ($files as $file) {
            if (! File::exists($file['source'])) {
                continue;
            }

            $stored = $this->copyToStorage($file['source'], 'portfolio/profile-photos');

            ProfilePhoto::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'path' => $stored],
                ['alt' => $file['alt'], 'sort_order' => $sort++, 'is_active' => true],
            );
        }
    }

    /**
     * @return array<string, ProjectCategory>  keyed by slug
     */
    protected function createProjectCategories(Portfolio $portfolio): array
    {
        $defs = [
            ['name' => 'SaaS',        'slug' => 'saas',        'color' => '#5eead4', 'sort_order' => 1],
            ['name' => 'Healthcare',  'slug' => 'healthcare',  'color' => '#ef4444', 'sort_order' => 2],
            ['name' => 'eCommerce',   'slug' => 'ecommerce',   'color' => '#f59e0b', 'sort_order' => 3],
            ['name' => 'Finance',     'slug' => 'finance',     'color' => '#10b981', 'sort_order' => 4],
            ['name' => 'Enterprise',  'slug' => 'enterprise',  'color' => '#6366f1', 'sort_order' => 5],
        ];

        $categories = [];

        foreach ($defs as $def) {
            $categories[$def['slug']] = ProjectCategory::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'slug' => $def['slug']],
                $def + ['is_active' => true],
            );
        }

        return $categories;
    }

    protected function createProjects(Portfolio $portfolio, array $cats): void
    {
        $projects = [
            [
                'title'    => 'Remetric Health',
                'slug'     => 'remetric-health',
                'tagline'  => 'US Healthcare · Remote Patient Monitoring',
                'summary'  => 'US healthcare platform for real-time remote patient monitoring across connected devices and clinics.',
                'industry' => 'Healthcare',
                'client'   => 'Remetric Health (USA)',
                'role'     => 'Senior Backend Developer',
                'live_url' => 'https://www.remetrichealth.com/',
                'image'    => 'remetrichealth.png',
                'cats'     => ['saas', 'healthcare'],
                'tech'     => ['Laravel', 'MySQL', 'REST API', 'EHR Integration'],
                'features' => [
                    'Real-time monitoring of vitals (BP, glucose, SpO2, weight, temperature)',
                    'Provider alerts and proactive intervention workflow',
                    'EHR integration with custom clinician reports',
                    'HIPAA-aligned data security and access control',
                ],
                'description' => 'Remetric Health is a US-based Remote Patient Monitoring (RPM) platform helping clinics and home-health agencies track patients\' vital signs in real time, reduce hospital readmissions and improve chronic-disease care.',
            ],
            [
                'title'    => 'ARTdynamix®',
                'slug'     => 'artdynamix',
                'tagline'  => 'CMS · Performing Arts Organizations',
                'summary'  => 'CMS built for performing arts organizations — ticketing, fundraising, and SEO in one place.',
                'industry' => 'Arts & Culture',
                'client'   => 'Dream Warrior Group (USA)',
                'role'     => 'Team Lead, Laravel Developer',
                'live_url' => 'https://www.artdynamix.net/',
                'image'    => 'artdynamix.png',
                'cats'     => ['saas'],
                'tech'     => ['Laravel', 'Vue.js', 'MySQL'],
                'features' => [
                    'Drag-and-drop page builder',
                    'Ticketing & fundraising data sync',
                    'Built-in SEO optimisation',
                    'Facility rental promotions',
                ],
                'description' => 'A powerful CMS / website builder tailored to The Arts. Includes time-saving tools to manage marketing, classes, board rooms, fundraising and events.',
            ],
            [
                'title'    => 'Dr. iQ',
                'slug'     => 'dr-iq',
                'tagline'  => 'Telemedicine · AI Triage + Secure EHR',
                'summary'  => 'Telemedicine platform with AI triage, secure medical records, and integrated payments.',
                'industry' => 'Healthcare',
                'client'   => 'AT Tech',
                'role'     => 'Laravel Developer',
                'live_url' => null,
                'image'    => 'dr_iq.png',
                'cats'     => ['saas', 'healthcare'],
                'tech'     => ['Laravel', 'MySQL', 'AI Chatbot', 'Payment Gateway'],
                'features' => [
                    'Patient appointment booking with qualified doctors',
                    'AI chatbot for general health questions',
                    'Secure medical record uploads (X-rays, lab results)',
                    'Doctor schedule + appointment management',
                    'Integrated payment system',
                ],
                'description' => 'A telemedicine platform that connects patients with doctors for remote consultations, prescriptions and follow-ups. Built with strict patient-data privacy.',
            ],
            [
                'title'    => 'Dairy Queen',
                'slug'     => 'dairy-queen',
                'tagline'  => 'Finance System · US Food Franchise',
                'summary'  => 'Finance management system for a major US food franchise with multi-store sales, payroll and reporting.',
                'industry' => 'Food & Beverage',
                'client'   => 'Dream Warrior Group (USA) for Dairy Queen',
                'role'     => 'Architect, Backend Lead',
                'live_url' => 'https://dq.shakeeliqbal.com/',
                'demo_credentials' => 'admin@yopmail.com / password',
                'image'    => 'dairy_queen.png',
                'cats'     => ['finance', 'enterprise'],
                'tech'     => ['Laravel 11', 'MySQL', 'Spatie Permissions'],
                'features' => [
                    'Role / permission management with Spatie',
                    'Store profiles, hours, performance & financial summaries',
                    'Daily envelope: net sales, payouts, cash control',
                    'Monthly factsheets, manager bonus calculations',
                    '11+ analytical reports incl. YTD, Sales Goal Bonus, Over/Short',
                ],
                'description' => 'A robust Financial Management System for Dairy Queen with role-based access, store operations, daily / monthly reporting and bonus computation across the franchise.',
            ],
            [
                'title'    => 'Retbajri',
                'slug'     => 'retbajri',
                'tagline'  => 'Multi-Vendor eCommerce Marketplace',
                'summary'  => 'Multi-vendor eCommerce marketplace connecting buyers and sellers of construction materials.',
                'industry' => 'eCommerce',
                'client'   => 'Retbajri',
                'role'     => 'Full-stack Developer',
                'live_url' => 'https://retbajri.shakeeliqbal.com/',
                'demo_credentials' => 'admin@retbajri.com / password',
                'image'    => 'retbajri.png',
                'cats'     => ['ecommerce'],
                'tech'     => ['Laravel', 'Vue.js', 'MySQL', 'OTP Auth'],
                'features' => [
                    'Three roles: Admin, Seller, Buyer',
                    'OTP-based account verification',
                    'Sellers buy ad plans, post products and create offers',
                    'Buyers request quotations from sellers',
                    'Order, plan and analytics dashboards per role',
                ],
                'description' => 'A multi-vendor marketplace for buying and selling construction materials, with separate dashboards for admins, sellers and buyers and a complete quotation workflow.',
            ],
            [
                'title'    => 'KeyWord Caddy',
                'slug'     => 'keywordcaddy',
                'tagline'  => 'SEO SaaS · Keyword Analysis Tool',
                'summary'  => 'SEO SaaS that helps writers rank higher by tracking keyword usage inside their content in real time.',
                'industry' => 'SaaS',
                'client'   => 'KeyWord Caddy',
                'role'     => 'Laravel Developer',
                'live_url' => 'https://keywordcaddy.com/',
                'image'    => 'keywordcaddy.png',
                'cats'     => ['saas'],
                'tech'     => ['Laravel', 'jQuery', 'MySQL'],
                'features' => [
                    'Plan / package billing',
                    'Multiple projects per user',
                    'Live keyword usage analysis',
                    'Word-count and density metrics',
                ],
                'description' => 'KeywordCaddy simplifies and accelerates the process of creating SEO content that ranks high in search engines.',
                'is_saas'  => true,
                'saas_url' => 'https://keywordcaddy.com/',
            ],
            [
                'title'    => 'Qurbani Pro',
                'slug'     => 'qurbani-pro',
                'tagline'  => 'Order + Logistics · Barcode Tracking',
                'summary'  => 'Online ordering + barcode-tracked logistics system used across outlets for live order status updates.',
                'industry' => 'Logistics',
                'client'   => 'Qurbani Pro',
                'role'     => 'Full-stack Developer',
                'live_url' => 'https://qurbanipro.com/',
                'image'    => 'qurbanipro.png',
                'cats'     => ['ecommerce', 'enterprise'],
                'tech'     => ['Laravel', 'MySQL', 'SMS Gateway', 'Barcode'],
                'features' => [
                    'Online + outlet booking with POS receipts',
                    'Barcode tags scanned at slaughter / pack / load / deliver',
                    'SMS notifications at every stage',
                    'Outlet, Finance, Manager and Admin dashboards',
                    'Live Eid-day progress and analytics',
                ],
                'description' => 'An online qurbani ordering system and barcode-tracked process monitoring platform used by outlets, finance and operations teams during peak Eid days.',
            ],
            [
                'title'    => 'HRIS',
                'slug'     => 'hris',
                'tagline'  => 'HR Platform · Payroll + Recruitment',
                'summary'  => 'Complete HR platform covering payroll, leave, recruitment, appraisal, and employee records.',
                'industry' => 'HR',
                'client'   => 'CHIP Consulting (Pakistan)',
                'role'     => 'Team Lead',
                'live_url' => 'https://hris.ctcorg.com/',
                'image'    => 'hris.png',
                'cats'     => ['enterprise'],
                'tech'     => ['Laravel', 'Vue.js', 'MySQL'],
                'features' => [
                    'Recruitment, leave, contract, payroll modules',
                    'Training, disciplinary, appraisal, insurance',
                    'Employee profile + activity log management',
                    'Reports: deployment, employee, training, appraisal, history',
                ],
                'description' => 'A complete Human Resource Information System covering recruitment to retirement with role-based dashboards and rich reporting.',
            ],
            [
                'title'    => 'Return Profit X',
                'slug'     => 'return-profit-x',
                'tagline'  => 'Crypto Trading Platform',
                'summary'  => 'Crypto trading platform for buying, selling, and managing digital-asset portfolios.',
                'industry' => 'Fintech',
                'client'   => 'Return Profit X',
                'role'     => 'Laravel Developer',
                'live_url' => 'https://returnprofitx.shakeeliqbal.com/',
                'image'    => 'returnprofitx.png',
                'cats'     => ['saas', 'finance'],
                'tech'     => ['Laravel', 'MySQL', 'Crypto APIs'],
                'features' => [
                    'Buy / sell crypto',
                    'Portfolio tracking',
                    'Plan-based access',
                ],
                'description' => 'A crypto trading platform for buying, selling and managing digital assets.',
            ],
            [
                'title'    => 'YoWorld Info',
                'slug'     => 'yoworld-info',
                'tagline'  => 'Gaming Info Portal · Price Guide',
                'summary'  => 'Gaming information portal with a live price guide and item wiki for YoWorld players.',
                'industry' => 'Gaming',
                'client'   => 'YoWorld Info',
                'role'     => 'Laravel Developer',
                'live_url' => 'https://yoworld.info/',
                'image'    => 'yoworldinfo.png',
                'cats'     => ['saas'],
                'tech'     => ['Laravel', 'MySQL'],
                'features' => [
                    'Item & price catalogue',
                    'Search and filtering',
                    'Community-curated content',
                ],
                'description' => 'A gaming information portal: item wiki, price guide and community resources for YoWorld players.',
            ],
            [
                'title'    => 'OES — Online Exam System',
                'slug'     => 'online-exam-system',
                'tagline'  => 'Online Exam SaaS · CBT Platform',
                'summary'  => 'Online exam SaaS for computer-based testing with question banks, timers, and auto-grading.',
                'industry' => 'EdTech',
                'client'   => 'CHIP Consulting',
                'role'     => 'Team Lead',
                'live_url' => 'http://oes.chipcosulting.org/',
                'image'    => 'oes.png',
                'cats'     => ['saas', 'enterprise'],
                'tech'     => ['Laravel', 'Vue.js', 'MySQL'],
                'features' => [
                    'Define exam patterns and question banks',
                    'MCQ + descriptive sections, configurable timers',
                    'Bulk question upload + import from past tests',
                    'Examiner role for descriptive grading',
                    'Project + per-test reports',
                ],
                'description' => 'A computer-based test system for conducting paperless exams with rich admin tooling, candidate management and detailed reporting.',
            ],
        ];

        foreach ($projects as $i => $p) {
            $imgPath = public_path('img/portfolio/'.$p['image']);
            $stored  = File::exists($imgPath) ? $this->copyToStorage($imgPath, 'portfolio/projects') : null;

            $project = Project::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'slug' => $p['slug']],
                [
                    'title'             => $p['title'],
                    'tagline'           => $p['tagline'],
                    'summary'           => $p['summary'],
                    'description'       => $p['description'] ?? null,
                    'client'            => $p['client'] ?? null,
                    'role'              => $p['role'] ?? null,
                    'industry'          => $p['industry'] ?? null,
                    'tech_stack'        => $p['tech'] ?? null,
                    'key_features'      => $p['features'] ?? null,
                    'live_url'          => $p['live_url'] ?? null,
                    'demo_credentials'  => $p['demo_credentials'] ?? null,
                    'is_saas'           => $p['is_saas'] ?? false,
                    'saas_url'          => $p['saas_url'] ?? null,
                    'is_for_sale'       => false,
                    'is_published'      => true,
                    'is_featured'       => $i < 4,
                    'sort_order'        => ($i + 1) * 10,
                ],
            );

            // Sync category pivot
            $catIds = collect($p['cats'])->map(fn ($slug) => $cats[$slug]?->id)->filter()->values()->all();
            $project->categories()->sync($catIds);

            // Primary image
            if ($stored) {
                ProjectImage::updateOrCreate(
                    ['project_id' => $project->id, 'path' => $stored],
                    [
                        'alt'        => $p['title'],
                        'is_primary' => true,
                        'sort_order' => 1,
                    ],
                );
            }
        }
    }

    protected function createSkills(Portfolio $portfolio): void
    {
        $tree = [
            'Backend' => [
                'icon'   => 'server',
                'skills' => [
                    'Laravel (PHP)', 'PHP 8.x', 'CodeIgniter', 'OOP & MVC',
                    'RESTful APIs', 'Queues & Jobs', 'Stored Procedures', 'Payment Gateways',
                ],
            ],
            'Database' => [
                'icon'   => 'database',
                'skills' => ['MySQL', 'PostgreSQL', 'Redis & Caching', 'Schema Design'],
            ],
            'Frontend' => [
                'icon'   => 'layout',
                'skills' => ['Livewire', 'Vue.js', 'JavaScript', 'jQuery & AJAX', 'HTML5', 'CSS3'],
            ],
            'DevOps' => [
                'icon'   => 'box',
                'skills' => ['Docker', 'Git & CI/CD', 'Linux', 'Nginx'],
            ],
        ];

        $catSort = 1;

        foreach ($tree as $catName => $cat) {
            $category = SkillCategory::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'slug' => Str::slug($catName)],
                [
                    'name'       => $catName,
                    'icon'       => $cat['icon'] ?? null,
                    'sort_order' => $catSort++,
                    'is_active'  => true,
                ],
            );

            $skillSort = 1;

            foreach ($cat['skills'] as $name) {
                Skill::updateOrCreate(
                    ['portfolio_id' => $portfolio->id, 'skill_category_id' => $category->id, 'name' => $name],
                    ['sort_order' => $skillSort++, 'is_active' => true],
                );
            }
        }
    }

    protected function createExperiences(Portfolio $portfolio): void
    {
        $items = [
            [
                'company'     => 'Point of IT (Pvt) Ltd',
                'role'        => 'Senior Laravel Developer',
                'subtitle'    => 'Remetric Health (US-Based Healthcare Project)',
                'start_date'  => '2025-01-01',
                'end_date'    => '2025-07-31',
                'is_current'  => false,
                'description' => 'Senior Backend Developer for a US healthcare platform. Built secure Laravel APIs, optimized complex MySQL procedures, and improved performance across patient modules.',
            ],
            [
                'company'     => 'Dream Warrior Group',
                'role'        => 'Team Lead & Laravel Developer',
                'subtitle'    => 'ARTdynamix® and Dairy Queen',
                'start_date'  => '2024-04-01',
                'end_date'    => '2024-12-31',
                'is_current'  => false,
                'description' => 'Built ARTdynamix®, a CMS for performing arts organizations. Led the Dairy Queen project — architecture, backend, and performance — delivering scalable, secure, business-focused systems for a major US food franchise.',
            ],
            [
                'company'     => 'AT Tech',
                'role'        => 'Laravel Developer',
                'subtitle'    => 'Dr. iQ Telemedicine',
                'start_date'  => '2019-09-01',
                'end_date'    => '2024-04-30',
                'is_current'  => false,
                'description' => 'Built Dr. iQ telemedicine platform for online consultations and prescriptions. Focused on patient data security and robust backend performance.',
            ],
        ];

        foreach ($items as $i => $item) {
            Experience::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'company' => $item['company'], 'role' => $item['role']],
                $item + ['portfolio_id' => $portfolio->id, 'sort_order' => ($i + 1) * 10, 'is_visible' => true],
            );
        }
    }

    protected function createEducations(Portfolio $portfolio): void
    {
        $items = [
            [
                'institution' => 'International Islamic University Islamabad',
                'degree'      => 'BS in Software Engineering',
                'field'       => 'Software Engineering',
                'start_date'  => '2014-08-01',
                'end_date'    => '2019-01-31',
                'description' => 'Specialized in software architecture, backend development, and databases.',
            ],
            [
                'institution' => 'Sir Syed College, Wah Cantt',
                'degree'      => 'FSc (Pre-Engineering)',
                'field'       => 'Pre-Engineering',
                'start_date'  => '2012-09-01',
                'end_date'    => '2014-06-30',
                'description' => 'Focused on Physics, Mathematics, and Computer Science.',
            ],
        ];

        foreach ($items as $i => $item) {
            Education::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'institution' => $item['institution'], 'degree' => $item['degree']],
                $item + ['portfolio_id' => $portfolio->id, 'sort_order' => ($i + 1) * 10, 'is_visible' => true],
            );
        }
    }

    protected function createServices(Portfolio $portfolio): void
    {
        $items = [
            ['title' => 'Custom Web Applications',     'summary' => 'Tailored Laravel-based web apps that solve your unique business problems — from internal dashboards to full-scale platforms.'],
            ['title' => 'SaaS Platform Development',   'summary' => 'Launch your own SaaS product with secure multi-tenant architecture, payment integration, and scalable backend systems.'],
            ['title' => 'eCommerce & Multi-Vendor Stores', 'summary' => 'Fully functional online stores with product management, secure checkout, vendor management and more.'],
            ['title' => 'API & Backend Development',   'summary' => 'Robust REST APIs and backend logic that power mobile apps, frontend interfaces, and integrations.'],
            ['title' => 'Database Design & Optimization', 'summary' => 'Efficient MySQL & PostgreSQL databases, performance optimisation, and large-scale data management.'],
            ['title' => 'Enterprise Systems',          'summary' => 'Healthcare platforms, HR systems, inventory and finance management — built to enterprise standards.'],
            ['title' => 'Performance Optimization',    'summary' => 'Speed enhancements, code refactoring, and security audits for existing Laravel applications.'],
        ];

        foreach ($items as $i => $item) {
            Service::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'slug' => Str::slug($item['title'])],
                [
                    'title'      => $item['title'],
                    'summary'    => $item['summary'],
                    'sort_order' => ($i + 1) * 10,
                    'is_active'  => true,
                ],
            );
        }
    }

    protected function createTestimonials(Portfolio $portfolio): void
    {
        $items = [
            [
                'quote'   => 'Working with Shakeel was a game-changer for our health tech platform. He streamlined our Laravel backend and built scalable APIs that significantly boosted performance and reliability.',
                'author'  => "Michael O'Connor", 'role' => 'Tech Lead', 'company' => 'Remetric Health', 'country' => 'USA',
            ],
            [
                'quote'   => 'Shakeel helped us develop a multi-vendor eCommerce platform from the ground up. His Laravel expertise ensured smooth functionality, payment integrations, and a solid backend structure.',
                'author'  => 'Sarah Khan', 'role' => 'Product Manager', 'company' => 'ShopNow Hub', 'country' => 'UK',
            ],
            [
                'quote'   => "We collaborated on a Laravel-based HR system, and Shakeel's work on modules, API development, and database design made it a success. It's now actively used by multiple departments.",
                'author'  => 'Imran Rafiq', 'role' => 'HR Systems Lead', 'company' => 'CHIP Consulting', 'country' => 'Pakistan',
            ],
            [
                'quote'   => 'Shakeel rebuilt our analytics dashboard using PHP, Laravel, and MySQL. Thanks to his optimization, our report load time dropped from 45 seconds to under 5 seconds.',
                'author'  => 'Hans Dekker', 'role' => 'CTO', 'company' => 'DataSight Analytics', 'country' => 'Netherlands',
            ],
            [
                'quote'   => "Shakeel worked on Dairy Queen's Laravel-based finance management system. His backend development ensured smooth handling of daily sales data, monthly revenue reports, and custom analytics dashboards. For a brand as large and trusted as Dairy Queen, accuracy and performance were critical — and Shakeel delivered both.",
                'author'  => 'Amanda Lopez', 'role' => 'Project Manager', 'company' => 'Dream Warrior Group', 'country' => 'USA',
            ],
            [
                'quote'   => 'Shakeel played a key role in the Laravel development of ARTdynamix®, our CMS for performing arts organizations. His contributions helped us build a modular, customizable system used by theaters and museums across the U.S.',
                'author'  => 'Amanda Lopez', 'role' => 'Project Manager', 'company' => 'Dream Warrior Group', 'country' => 'USA',
            ],
            [
                'quote'   => 'Our Laravel SaaS platform had critical bugs until Shakeel stepped in. He quickly diagnosed the issues and refactored backend logic with great results.',
                'author'  => 'Jasper Müller', 'role' => 'Founder', 'company' => 'CodeBridge Systems', 'country' => 'Germany',
            ],
            [
                'quote'   => 'We needed a backend developer for our inventory system. Shakeel developed admin dashboards, REST APIs, and ensured top-notch security and performance.',
                'author'  => 'Nida Patel', 'role' => 'Operations Manager', 'company' => 'StockPilot ERP', 'country' => 'UAE',
            ],
        ];

        foreach ($items as $i => $item) {
            Testimonial::updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'author'       => $item['author'],
                    'company'      => $item['company'],
                ],
                $item + [
                    'portfolio_id' => $portfolio->id,
                    'sort_order'   => ($i + 1) * 10,
                    'is_visible'   => true,
                    'rating'       => 5,
                ],
            );
        }
    }

    protected function createWhyPoints(Portfolio $portfolio): void
    {
        $items = [
            ['label' => '01 — Craft',         'title' => 'Senior-level quality',    'description' => '6+ years shipping production Laravel in healthcare, finance, and SaaS. Clean code, tested and documented — no handoff surprises.'],
            ['label' => '02 — Mindset',       'title' => 'Business-first thinking', 'description' => 'I ask "why" before "how." Every feature is measured against your goals, not a spec sheet.'],
            ['label' => '03 — Communication', 'title' => 'Clear and consistent',    'description' => "Async updates, zero jargon, timezone-flexible. You'll always know what's shipping, what's blocked, and when."],
            ['label' => '04 — Longevity',     'title' => 'Built to scale',          'description' => "Secure APIs, optimized queries, sensible architecture. Today's code won't be tomorrow's tech debt."],
        ];

        foreach ($items as $i => $item) {
            WhyPoint::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'title' => $item['title']],
                $item + ['portfolio_id' => $portfolio->id, 'sort_order' => ($i + 1) * 10, 'is_visible' => true],
            );
        }
    }

    protected function createBlogStarters(Portfolio $portfolio, User $user): void
    {
        $cats = collect([
            ['name' => 'Laravel',        'slug' => 'laravel',        'description' => 'Tips, tricks and patterns for production Laravel apps.'],
            ['name' => 'SaaS',           'slug' => 'saas',           'description' => 'Building, launching and scaling SaaS products.'],
            ['name' => 'Career',         'slug' => 'career',         'description' => 'Notes on freelancing, remote work and growth.'],
        ])->map(fn ($c, $i) => BlogCategory::updateOrCreate(
            ['portfolio_id' => $portfolio->id, 'slug' => $c['slug']],
            $c + ['portfolio_id' => $portfolio->id, 'sort_order' => ($i + 1) * 10, 'is_active' => true],
        ));

        $tags = collect(['Laravel', 'PHP', 'MySQL', 'Livewire', 'Performance', 'Architecture'])
            ->map(fn ($name) => BlogTag::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'slug' => Str::slug($name)],
                ['portfolio_id' => $portfolio->id, 'name' => $name],
            ));

        // One starter draft so the editor has content to load
        $post = Post::updateOrCreate(
            ['portfolio_id' => $portfolio->id, 'slug' => 'welcome-to-the-blog'],
            [
                'blog_category_id'      => $cats->first()->id,
                'author_id'             => $user->id,
                'title'                 => 'Welcome to the blog',
                'excerpt'               => 'A quick hello from Shakeel — what to expect from this blog and how it\'s built.',
                'content'               => "# Welcome\n\nThis is the first post on the new portfolio + blog system. From here on, every project, testimonial and post is editable from `/admin`.\n\n- Built on Laravel 12 + Livewire 4 + Flux 2\n- Markdown content with featured image, SEO meta and tags\n- Soft-deletes on everything",
                'content_format'        => 'markdown',
                'status'                => Post::STATUS_DRAFT,
                'is_featured'           => false,
                'sort_order'            => 10,
            ],
        );

        $post->tags()->sync($tags->whereIn('slug', ['laravel', 'livewire'])->pluck('id'));
    }

    /**
     * Copy a public-path image into the configured storage disk and return
     * its relative path. If the file already exists at the destination it
     * is reused (idempotent seeding).
     */
    protected function copyToStorage(string $sourcePath, string $folder): string
    {
        $name        = basename($sourcePath);
        $destination = trim($folder, '/').'/'.$name;

        if (! Storage::disk($this->disk)->exists($destination)) {
            Storage::disk($this->disk)->put($destination, File::get($sourcePath));
        }

        return $destination;
    }
}
