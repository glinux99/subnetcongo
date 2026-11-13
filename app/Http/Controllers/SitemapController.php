<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * sitemap.xml public — pages clé, pages dynamiques publiées et
     * cours publiés de la marketplace. Mis en cache 1 h.
     */
    public function index(): Response
    {
        $urls = Cache::remember('sitemap.xml', 3600, function () {
            $static = [
                ['loc' => route('landing'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('academy'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('marketplace.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('blog'), 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['loc' => route('about'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('contact'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('realizations'), 'priority' => '0.5', 'changefreq' => 'weekly'],
                ['loc' => route('testimonials'), 'priority' => '0.4', 'changefreq' => 'monthly'],
                ['loc' => route('live-sessions.public'), 'priority' => '0.6', 'changefreq' => 'daily'],
                ['loc' => route('mentions-legales'), 'priority' => '0.1', 'changefreq' => 'yearly'],
                ['loc' => route('confidentialite'), 'priority' => '0.1', 'changefreq' => 'yearly'],
            ];

            $dynamicPages = Page::query()
                ->where('is_published', true)
                ->get(['slug', 'updated_at'])
                ->map(fn (Page $page) => [
                    'loc' => route('web.page', $page->slug),
                    'lastmod' => optional($page->updated_at)->toW3cString(),
                    'priority' => '0.4',
                    'changefreq' => 'monthly',
                ])
                ->all();

            $courses = Course::query()
                ->where('status', Course::STATUS_PUBLISHED)
                ->get(['slug', 'updated_at'])
                ->map(fn (Course $course) => [
                    'loc' => route('marketplace.show', $course->slug),
                    'lastmod' => optional($course->updated_at)->toW3cString(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ])
                ->all();

            return array_merge($static, $dynamicPages, $courses);
        });

        $xml = $this->render($urls);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function render(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $loc = htmlspecialchars((string) $url['loc'], ENT_XML1, 'UTF-8');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            }
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return $xml;
    }
}
