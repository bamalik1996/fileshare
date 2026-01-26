<?php

namespace App\Http\Controllers;

class SitemapController extends Controller
{
    public function index()
    {
        // Force production URL as requested
        $baseUrl = 'https://airtoshare.app';

        $blogs = BlogController::getBlogs();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        // 1. Homepage
        $content .= '<!--  Homepage  -->';
        $content .= '<url>';
        $content .= '<loc>'.$baseUrl.'</loc>';
        $content .= '<lastmod>'.date('Y-m-d').'</lastmod>';
        $content .= '<changefreq>daily</changefreq>';
        $content .= '<priority>1.0</priority>';
        $content .= '</url>';

        // 2. Static Pages definition
        $pages = [
            'how-it-works' => ['priority' => '0.8', 'freq' => 'weekly'],
            'faq' => ['priority' => '0.7', 'freq' => 'weekly'],
            'feedback' => ['priority' => '0.6', 'freq' => 'monthly'],
            'blog' => ['priority' => '0.9', 'freq' => 'daily'],
            //'smart-file-organization' => ['priority' => '0.8', 'freq' => 'monthly'],
            'coming-soon' => ['priority' => '0.5', 'freq' => 'monthly'],
        ];

        foreach ($pages as $path => $meta) {
            $content .= '<!--  '.ucwords(str_replace('-', ' ', $path)).'  -->';
            $content .= '<url>';
            $content .= '<loc>'.$baseUrl.'/'.$path.'</loc>';
            $content .= '<lastmod>'.date('Y-m-d').'</lastmod>';
            $content .= '<changefreq>'.$meta['freq'].'</changefreq>';
            $content .= '<priority>'.$meta['priority'].'</priority>';
            $content .= '</url>';
        }

        // 3. Blog Posts
        foreach ($blogs as $blog) {
            $content .= '<!--  Blog: '.htmlspecialchars($blog['title']).'  -->';
            $content .= '<url>';
            $content .= '<loc>'.$baseUrl.'/blog/'.$blog['slug'].'</loc>';
            $content .= '<lastmod>'.date('Y-m-d', strtotime($blog['date'])).'</lastmod>';
            $content .= '<changefreq>monthly</changefreq>';
            $content .= '<priority>0.7</priority>';
            $content .= '</url>';
        }

        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }
}
