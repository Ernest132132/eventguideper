<script>
        (function() {
            let lastActivityTime = Date.now();
            let lastPingTime = 0;
            const PING_INTERVAL = 5 * 60 * 1000; // 5 minutes

            function recordActivity() {
                lastActivityTime = Date.now();
            }

            // Listen for any user interaction
            ['click', 'mousemove', 'keydown', 'touchstart', 'scroll'].forEach(event => {
                document.addEventListener(event, recordActivity, { passive: true });
            });

            // Check periodically
            setInterval(() => {
                const now = Date.now();
                // Send ping ONLY if user was active recently AND it's been > 5 mins since last ping
                if (now - lastActivityTime < PING_INTERVAL && now - lastPingTime > PING_INTERVAL) {
                    fetch('api_heartbeat.php', { method: 'POST' })
                        .then(() => { lastPingTime = now; })
                        .catch(() => {}); // Silent fail
                }
            }, 60 * 1000); // Check every minute
            
            // Send one ping on initial load
            fetch('api_heartbeat.php', { method: 'POST' }).then(() => { lastPingTime = Date.now(); }).catch(()=>{});
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Disable Right Click
            document.addEventListener('contextmenu', (e) => e.preventDefault());

            // 2. Disable Keyboard Shortcuts (F12, Ctrl+Shift+I, Ctrl+U, etc.)
            document.onkeydown = function(e) {
                if (e.keyCode == 123) { // F12
                    return false;
                }
                if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) { // Ctrl+Shift+I
                    return false;
                }
                if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) { // Ctrl+Shift+C
                    return false;
                }
                if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) { // Ctrl+Shift+J
                    return false;
                }
                if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) { // Ctrl+U (View Source)
                    return false;
                }
            };
        });
    </script>

</body>
</html>