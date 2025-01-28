<nav x-data="{ open: false }" class="bg-blue-700">
    <!-- Mobile & Desktop Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="/sistem/public/index.php" class="text-white font-bold text-xl mr-8">
                    <?= htmlspecialchars(SITE_NAME) ?>
                </a>
                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex space-x-4">
                    <a href="/sistem/public/index.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Beranda</a>
                    <a href="/sistem/public/books.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Buku</a>
                    <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Kontak</a>
                </div>
            </div>

            <!-- Authentication Links -->
            <div class="hidden md:flex space-x-4">
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="/sistem/public/auth/login.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                    <a href="/sistem/public/auth/register.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium">Daftar</a>
                <?php else: ?>
                    <div class="flex items-center space-x-4">
                        <span class="text-white text-sm">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                        <a href="/sistem/public/auth/logout.php" class="text-white hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button
                    @click="open = !open"
                    type="button"
                    class="bg-blue-600 inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-blue-500 focus:outline-none"
                    aria-expanded="false">
                    <span class="sr-only">Toggle menu</span>
                    <svg
                        x-show="!open"
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg
                        x-show="open"
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open" class="md:hidden bg-blue-600">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/sistem/public/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Beranda</a>
            <a href="/sistem/public/books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Buku</a>
            <a href="/sistem/public/contact.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Kontak</a>
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="/sistem/public/auth/login.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Login</a>
                <a href="/sistem/public/auth/register.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Daftar</a>
            <?php else: ?>
                <div class="px-3 py-2 text-white">
                    <span class="block text-sm mb-2">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    <a href="/sistem/public/auth/logout.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-500">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>