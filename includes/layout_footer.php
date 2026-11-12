<?php
/**
 * Layout penutup halaman (footer + script).
 * Variabel: $extraScripts (opsional, HTML mentah)
 */
?>
    <footer>
        <div class="footer-content text-center">
            <div class="social-links" style="justify-content: center; display: flex; gap: 20px; margin-bottom: 15px;">
                <a href="https://www.instagram.com/barasuara" target="_blank" rel="noopener noreferrer"
                    aria-label="Instagram Barasuara"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/barasuara" target="_blank" rel="noopener noreferrer"
                    aria-label="YouTube Barasuara"><i class="fab fa-youtube"></i></a>
                <a href="https://open.spotify.com/intl-id/artist/5RbI1PF4n02wWKmgjk7hAE?si=zHUmzxMMSkq_e1ENO2OcPQ&nd=1&dlsi=a7cf13a4230f4098"
                    target="_blank" rel="noopener noreferrer" aria-label="Spotify Barasuara"><i
                        class="fab fa-spotify"></i></a>
            </div>
            <p style="margin: 0; font-size: 0.9rem; color: #888;">
                © 2026 Barasuara Fans Project. Dikelola Oleh SIG Group.
                &middot; <a href="admin/login.php" style="color: #777; text-decoration: none; font-size: 0.82rem;"><i class="fas fa-user-shield"></i> Panel Admin</a>
            </p>
        </div>
    </footer>

    <script src="lib/script.js" defer></script>
    <?= $extraScripts ?? '' ?>
</body>

</html>
