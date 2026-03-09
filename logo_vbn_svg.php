<?php
declare(strict_types=1);

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

echo <<<'SVG'
<svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" class="logo-svg">
  <defs>
    <linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#2a1515;stop-opacity:1"></stop>
      <stop offset="100%" style="stop-color:#1a0f0f;stop-opacity:1"></stop>
    </linearGradient>
    <filter id="shadow">
      <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#000000" flood-opacity="0.8"></feDropShadow>
    </filter>
  </defs>
  <rect width="80" height="80" fill="url(#bgGradient)" rx="8"></rect>
  <rect x="3" y="3" width="74" height="74" fill="none" stroke="rgba(0,0,0,0.3)" stroke-width="1" rx="6"></rect>
  <rect width="80" height="80" fill="none" stroke="#8B0000" stroke-width="3" rx="8" class="logo-border"></rect>
  <text x="40" y="52" font-family="'IM Fell English', serif" font-size="28" fill="#8B0000" text-anchor="middle" font-weight="bold" letter-spacing="2" filter="url(#shadow)" class="logo-text">VbN</text>
</svg>
SVG;
