<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') - Vanguard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    <!-- Icon -->
    @if (config('app.env') === 'local')
        <link rel="icon" href="{{ asset('local_favicon.ico') }}" type="image/x-icon"/>
        <link rel="shortcut icon" href="{{ asset('local_favicon.png') }}" type="image/x-icon"/>
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
        <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon"/>
    @endif

    <!-- CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans text-gray-900 dark:text-white antialiased">
<div
    class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-200 dark:bg-gray-900 border-t-8 border-gray-900 dark:border-white">
    <div>
        <a href="/">
            <svg width="100%" height="100%" viewBox="0 0 1000 1000" preserveAspectRatio="none"
                 class="w-32 h-32 fill-current text-gray-900 dark:text-white">
                <g transform="translate(0,1000) scale(0.1,-0.1)" fill="currentColor" stroke="none">
                    <path
                        d="M4710 8174 c-301 -15 -596 -45 -885 -92 l-170 -27 265 -7 c1074 -26 2293 -289 2872 -619 108 -62 175 -122 213 -191 28 -49 30 -60 30 -158 0 -124 -21 -206 -118 -450 -222 -563 -659 -1254 -1180 -1864 -60 -71 -104 -125 -96 -121 87 51 471 389 694 611 667 662 1124 1318 1270 1819 115 398 -14 632 -425 769 -440 147 -1055 266 -1600 311 -187 15 -712 27 -870 19z"/>
                    <path
                        d="M3460 7930 c-211 -17 -496 -58 -665 -96 -98 -22 -240 -93 -296 -147 -113 -109 -162 -267 -139 -453 15 -119 77 -358 149 -572 207 -614 552 -1251 1007 -1857 113 -150 364 -458 384 -470 6 -4 -18 38 -54 92 -191 295 -429 765 -593 1173 -278 690 -414 1352 -325 1581 29 74 98 141 188 185 176 84 675 181 1184 230 226 21 921 30 1172 15 279 -17 676 -61 788 -88 14 -3 22 -3 19 1 -12 13 -360 117 -574 171 -430 110 -811 177 -1258 221 -180 18 -817 27 -987 14z"/>
                    <path
                        d="M3025 6730 c21 -181 96 -484 184 -740 235 -688 656 -1461 1097 -2010 226 -282 407 -458 534 -519 67 -32 84 -36 156 -36 75 0 87 3 165 42 131 65 351 256 669 579 526 536 968 1142 1306 1789 65 124 180 367 206 435 13 34 8 28 -118 -149 -125 -176 -300 -398 -464 -586 -157 -180 -624 -644 -806 -802 -307 -265 -671 -538 -813 -610 -64 -33 -83 -38 -140 -38 -82 1 -136 26 -271 128 -120 91 -394 363 -530 527 -450 541 -866 1244 -1122 1895 -53 136 -58 145 -53 95z"/>
                </g>
            </svg>
        </a>
    </div>
    <div class="w-full sm:max-w-xl mt-2">
        <div>
            <h1 class="text-4xl font-semibold text-gray-950 dark:text-white text-center mb-3">@yield('code')
                - @yield('title')</h1>
            <hr class="border-b-2 border-gray-800 dark:border-white mb-6">
            <p class="text-xl text-gray-900 dark:text-gray-300 text-center">@yield('message')</p>
            @hasSection('additional')
                <p class="text-base text-gray-800 dark:text-gray-400 text-center mt-3">@yield('additional')</p>
            @endif
        </div>
        @hasSection('linkURL')
            @hasSection('linkText')
            <section>
                <div class="text-center mt-6">
                    <a href="@yield('linkURL')"
                       class="text-sm text-gray-800 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">@yield('linkText')</a>
                </div>
            </section>
        @endif
        @endif
    </div>
</div>
</body>
</html>
