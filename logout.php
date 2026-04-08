<?php
/*
 * Project: COC Verification system for sidama region
 * Purpose: Master's Degree Thesis 
 * * Description: This software was developed to solve a real-world problem as part of 
 * the graduation requirements for the Master's Program.
 * * Author: Dawit Birru Hurisso
 * Institution: Czech University of Life Sciences Prague (CZU)
 * Graduate Year: 2026
 */
session_start();
require_once __DIR__ . '/includes/error-handler.php';
session_unset();
session_destroy();
header('Location: index.php');
exit;
