<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FunLynk - Gemini Style</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Galaxy background and glowing effects */
        body {
            background-color: #0d0d1a;
            background-image: url('https://images.unsplash.com/photo-1514829375354-15077227c491?q=80&w=2940&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #E0E0E0;
        }

        .navbar-custom {
            backdrop-filter: blur(10px) brightness(0.8);
            background-color: rgba(20, 20, 30, 0.7);
            border-bottom: 1px solid rgba(120, 120, 150, 0.2);
        }

        .card-custom {
            backdrop-filter: blur(8px) brightness(0.8);
            background-color: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(120, 120, 150, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease-in-out;
        }
        .card-custom:hover {
            transform: translateY(-5px);
        }

        .input-glow:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.5);
        }

        .funlynx-text-gradient {
            background-image: linear-gradient(to right, #FDE68A, #EC4899, #8B5CF6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Avatar border colors */
        .avatar-border-1 .mask { border: 2px solid #FDE68A; }
        .avatar-border-2 .mask { border: 2px solid #EC4899; }
        .avatar-border-3 .mask { border: 2px solid #8B5CF6; }
        .avatar-border-4 .mask { border: 2px solid #38BDF8; }
        .avatar-border-5 .mask { border: 2px solid #4ade80; }
        .avatar-border-6 .mask { border: 2px solid #f97316; }

        /* Glowing icon effect */
        .glowing-icon {
            filter: drop-shadow(0 0 5px currentColor);
            transition: all 0.2s ease-in-out;
        }
        .glowing-icon:hover {
            filter: drop-shadow(0 0 10px currentColor) brightness(1.2);
            transform: scale(1.05);
        }

        .icon-guitar { color: #EC4899; }
        .icon-rollerblades { color: #4ade80; }
        .icon-book { color: #FDE68A; }
        .icon-controller { color: #8B5CF6; }

        .activity-icon-container {
            width: 4rem;
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            backdrop-filter: blur(5px);
            background-color: rgba(50, 50, 70, 0.4);
            border: 1px solid rgba(120, 120, 150, 0.2);
            transition: all 0.2s ease-in-out;
        }
        .activity-icon-container:hover {
            background-color: rgba(70, 70, 90, 0.6);
            transform: translateY(-3px);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="font-sans">

    <!-- Navbar -->
    <div class="navbar navbar-custom fixed top-0 w-full z-50 p-4">
        <div class="flex-1">
            <a class="btn btn-ghost normal-case text-xl flex items-center gap-2">
                <img src="{{ asset('images/fl-logo-main.png') }}" alt="FunLynk Logo" class="h-10 w-auto">
                <div>
                    <span class="funlynx-text-gradient text-2xl font-bold">FunLynk</span>
                    <span class="block text-xs text-gray-400 opacity-75">SOCIAL ACTIVITY NETWORK</span>
                </div>
            </a>
        </div>
        <div class="flex-none gap-2">
            <ul class="menu menu-horizontal px-1">
                <li><a class="text-gray-300 hover:text-white hover:bg-transparent transition-colors duration-200">Feed</a></li>
                <li><a class="text-gray-300 hover:text-white hover:bg-transparent transition-colors duration-200">Find Activities</a></li>
                <li><a class="text-gray-300 hover:text-white hover:bg-transparent transition-colors duration-200">Community</a></li>
            </ul>
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                    <div class="w-10 rounded-full border-2 border-green-500 relative">
                        <img src="https://daisyui.com/images/stock/photo-1534528741775-53994a69daeb.jpg" />
                        <span class="badge badge-success badge-xs absolute bottom-0 right-0 transform translate-x-1/2 translate-y-1/2"></span>
                    </div>
                </label>
                <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                    <li><a class="justify-between">Profile</a></li>
                    <li><a>Settings</a></li>
                    <li><a>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto p-8 mt-24">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <!-- Trending Activities Card -->
            <div class="card card-custom p-6 shadow-xl w-full">
                <h2 class="card-title text-2xl font-bold mb-6">Trending Activities</h2>
                <div class="flex gap-4 overflow-x-auto pb-4">
                    <div class="activity-icon-container">
                        <svg class="h-10 w-10 glowing-icon icon-guitar" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.59 3.41L15.17 7.83l1.42 1.42 4.42-4.42c.78-.78.78-2.05 0-2.83-.79-.78-2.05-.78-2.83 0zM12 9c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                        </svg>
                    </div>
                    <div class="activity-icon-container">
                        <svg class="h-10 w-10 glowing-icon icon-rollerblades" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/>
                        </svg>
                    </div>
                    <div class="activity-icon-container">
                        <svg class="h-10 w-10 glowing-icon icon-book" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
                        </svg>
                    </div>
                    <div class="activity-icon-container">
                        <svg class="h-10 w-10 glowing-icon icon-controller" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21.58 16.09l-1.09-7.66C20.21 6.46 18.52 5 16.53 5H7.47C5.48 5 3.79 6.46 3.51 8.43l-1.09 7.66C2.2 17.63 3.39 19 4.94 19c.68 0 1.32-.27 1.8-.75L9 16h6l2.25 2.25c.48.48 1.13.75 1.8.75 1.56 0 2.75-1.37 2.53-2.91zM11 11H9v2H8v-2H6v-1h2V8h1v2h2v1zm4-1c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm2 3c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                        </svg>
                    </div>
                    <div class="activity-icon-container">
                        <svg class="h-10 w-10 glowing-icon icon-book" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2v-7.5l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7"/>
                        </svg>
                    </div>
                    <div class="activity-icon-container">
                        <svg class="h-10 w-10 glowing-icon icon-controller" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Find Your Crew Card -->
            <div class="card card-custom p-6 shadow-xl w-full col-span-1 md:col-span-2">
                <h2 class="card-title text-2xl font-bold mb-6">Find Your Crew</h2>
                <div class="form-control mb-6">
                    <label class="input input-bordered flex items-center gap-2 input-glow">
                        <input type="text" class="grow text-lg" placeholder="Search by Interest..." />
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-6 h-6 opacity-70 text-gray-400">
                            <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
                        </svg>
                    </label>
                </div>
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 justify-items-center">
                    @foreach(['avatar-border-1', 'avatar-border-2', 'avatar-border-3', 'avatar-border-4', 'avatar-border-5', 'avatar-border-6', 'avatar-border-1', 'avatar-border-2', 'avatar-border-3'] as $borderClass)
                    <div class="avatar {{ $borderClass }}">
                        <div class="w-16 rounded-full mask mask-circle">
                            <img src="https://daisyui.com/images/stock/photo-1534528741775-53994a69daeb.jpg" />
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer footer-center p-4 bg-transparent text-gray-400 mt-12">
        <aside>
            <p>Copyright © {{ date('Y') }} - All right reserved by FunLynk Inc.</p>
        </aside>
    </footer>

</body>
</html>

