        </div><!-- /auth-box -->
    </div><!-- /auth-panel -->
</div><!-- /auth-layout -->

<script>
// ── Status Orthanc ao vivo (AJAX para o próprio sistema) ──
(function() {
    fetch('/api/orthanc/ping', { signal: AbortSignal.timeout(4000) })
        .then(r => r.json())
        .then(data => {
            const dot    = document.getElementById('orthancDot');
            const status = document.getElementById('orthancStatus');
            const info   = document.getElementById('orthancInfo');
            if (data && data.online) {
                dot.classList.remove('offline');
                status.textContent = 'PACS Online';
                info.textContent   = 'Orthanc · ' + (data.total_studies ?? '--') + ' estudos · 46.225.51.122:8042';
            } else {
                dot.classList.add('offline');
                status.textContent = 'PACS Offline';
            }
        })
        .catch(() => {
            const dot = document.getElementById('orthancDot');
            if (dot) dot.classList.add('offline');
            const s = document.getElementById('orthancStatus');
            if (s) s.textContent = 'PACS Indisponível';
        });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
