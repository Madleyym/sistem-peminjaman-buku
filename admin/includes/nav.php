<nav x-data="{ open: false }" class="bg-gradient-to-r from-blue-600 to-blue-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/sistem/admin/admin-index.php" class="flex items-center group">
                        <i class="fas fa-book-reader text-white text-2xl mr-2 transform group-hover:scale-110 transition-transform"></i>
                        <span class="text-white font-bold text-xl"><?= htmlspecialchars(SITE_NAME) ?></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:block">
                    <div class="flex items-center space-x-4">
                        <a href="/sistem/public/index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-home mr-1"></i> Beranda
                        </a>
                        <a href="/sistem/public/books.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-1"></i> Buku
                        </a>
                        <a href="/sistem/public/contact.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-envelope mr-1"></i> Kontak
                        </a>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                            <!-- Jika login sebagai admin -->
                            <div class="flex items-center space-x-3">
                                <span class="text-white text-sm">
                                    <i class="fas fa-user-circle mr-1"></i>
                                    <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                                </span>
                                <a href="/sistem/admin/auth/logout.php"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <!-- Jika belum login -->
                                <a href="/sistem/admin/auth/login.php"
                                    class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login Admin
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Improved Mobile Menu Button -->
                <div class="md:hidden">
                    <button @click="open = !open" class="text-white hover:bg-blue-700 p-2 rounded-md transition-colors duration-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Mobile Menu with Smooth Transitions -->
        <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="md:hidden bg-blue-800">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/sistem/public/index.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-home mr-1"></i> Beranda
                </a>
                <a href="/sistem/public/books.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-book mr-1"></i> Buku
                </a>
                <a href="/sistem/public/contact.php" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition-colors duration-300">
                    <i class="fas fa-envelope mr-1"></i> Kontak
                </a>
            </div>
        </div>
    </nav>