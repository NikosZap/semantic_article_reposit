<?php require_once BASE_PATH .'includes/pub_sparql_queries.php';?>

<nav class="navbar navbar-expand-xl navbar-light shadow-sm py-2"
     style="background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">
    <div class="container-fluid px-3 px-lg-4">

        <a class="navbar-brand fw-semibold d-flex align-items-center mb-0 me-3"
           href="<?php echo BASE_URL;?>index.php"
           style="min-width: 0; font-size: 1rem;">
            <i class="bi bi-diagram-3 me-2"></i>
            <i class="bi bi-search me-2"></i>

            <span class="d-none d-sm-inline text-nowrap">Semantic Research Explorer</span>
            <span class="d-inline d-sm-none text-nowrap">SRE</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse mt-3 mt-xl-0" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-xl-0 align-items-xl-center gap-xl-1">
                <li class="nav-item">
                    <a class="nav-link text-dark text-nowrap px-xl-2" href="<?php echo BASE_URL;?>fulltext_search.php">
                        Keyword Search
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-dark text-nowrap px-xl-2" href="<?php echo BASE_URL;?>semantic_search.php">
                        Semantic Search
                    </a>
                </li>

                <?php if (isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark dropdown-toggle text-nowrap px-xl-2"
                       href="#"
                       id="usersDropdown"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        Users
                    </a>
                    <ul class="dropdown-menu shadow-sm" aria-labelledby="usersDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/manage_users.php">User Management</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/send_announcements.php">Send Announcements</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user']['is_logged_in']) && in_array($_SESSION['user']['role'], ['Admin', 'Researcher'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark dropdown-toggle text-nowrap px-xl-2"
                       href="#"
                       id="publicationsDropdown"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        Publications
                    </a>

                    <ul class="dropdown-menu shadow-sm" aria-labelledby="publicationsDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/insert_manual_publication.php">Insert New Publication Manually</a></li>
                        <?php if ($_SESSION['user']['role'] === 'Admin' || $_SESSION['user']['role'] === 'Researcher') { ?>
                        
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/manage_publications.php">Manage Publications</a></li>
                        <?php } ?>
                            <?php if ($_SESSION['user']['role'] === 'Admin') { ?>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo BASE_URL;?>pages/approve_publications.php">
                                    <span>Pending Publications</span>
                                    <?php if (countPendingPublications() > 0): ?>
                                        <span class="badge bg-danger ms-2"><?= countPendingPublications() ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo BASE_URL;?>pages/approve_claims.php">
                                    <span>Pending Claims</span>
                                    <?php if (countPendingClaims() > 0): ?>
                                        <span class="badge bg-danger ms-2"><?= countPendingClaims() ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php } ?>

                        <?php if ($_SESSION['user']['role'] === 'Researcher') { ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/claim_publication.php">Claim Publication</a></li>
                        <?php } ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Admin') { ?>
                <li class="nav-item">
                    <a class="nav-link text-dark text-nowrap px-xl-2" href="<?php echo BASE_URL;?>pages/statistics.php">
                        Statistics
                    </a>
                </li>
                <?php } ?>
            </ul>

            <ul class="navbar-nav mb-2 mb-xl-0 align-items-xl-center ms-xl-3">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link text-dark dropdown-toggle text-nowrap"
                           href="#"
                           id="userDropdown"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false">
                            <?php echo $_SESSION['user']['username'] ?>
                        </a>
                        <ul class="dropdown-menu shadow-sm dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>backend/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link text-dark dropdown-toggle text-nowrap"
                           href="#"
                           id="authDropdown"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false">
                            Log in / Register
                        </a>
                        <ul class="dropdown-menu shadow-sm dropdown-menu-end" aria-labelledby="authDropdown">
                            <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#loginModal">Log in</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL;?>pages/register.php">Register</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php include(BASE_PATH . 'modals/login_modal.php'); ?>