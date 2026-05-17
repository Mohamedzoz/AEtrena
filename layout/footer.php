    </main><!-- /main content -->

    <!-- Footer -->
    <footer class="px-4 md:px-8 py-4 border-t border-gray-100 text-center">
        <p class="text-[10px] text-gray-300 font-bold">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSetting('system_name','Aeterna ERP')); ?> — Powered by Aeterna Cabinetry
        </p>
    </footer>

</div><!-- /main layout -->

<!-- ═══ Notifications Drawer ══════════════════════════════════════ -->
<div id="notifDrawer" class="fixed top-16 left-4 right-4 md:left-auto md:right-8 md:w-96 bg-white rounded-3xl shadow-2xl border border-gray-100 z-50 hidden max-h-[70vh] overflow-hidden flex flex-col">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
        <h3 class="font-black text-sm text-charcoal">الإشعارات</h3>
        <div class="flex items-center gap-2">
            <button onclick="markAllRead()" class="text-[10px] font-black text-taupe hover:underline">تحديد الكل كمقروء</button>
            <button onclick="toggleNotifications()" class="text-gray-400 hover:text-charcoal"><i class="fas fa-times text-sm"></i></button>
        </div>
    </div>
    <div id="notifList" class="flex-1 overflow-y-auto px-3 py-2">
        <p class="text-center text-xs text-gray-400 py-8 font-bold">جاري التحميل...</p>
    </div>
</div>

<!-- ═══ Scripts ════════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://unpkg.com/lenis@1.0.45/dist/lenis.min.js"></script>

<script>
// ─── Sidebar ─────────────────────────────────────────────────────
function openSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebar-overlay');
    sb.classList.remove('translate-x-full');
    ov.classList.remove('hidden');
    setTimeout(() => ov.classList.remove('opacity-0'), 10);
}
function closeSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebar-overlay');
    sb.classList.add('translate-x-full');
    ov.classList.add('opacity-0');
    setTimeout(() => ov.classList.add('hidden'), 300);
}

// ─── Notifications ────────────────────────────────────────────────
let notifOpen = false;
function toggleNotifications() {
    const d = document.getElementById('notifDrawer');
    notifOpen = !notifOpen;
    if (notifOpen) {
        d.classList.remove('hidden');
        loadNotifications();
    } else {
        d.classList.add('hidden');
    }
}
function loadNotifications() {
    fetch('notif_handler.php?action=get')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notifList');
            if (!data.length) {
                list.innerHTML = '<p class="text-center text-xs text-gray-400 py-8 font-bold">لا توجد إشعارات</p>';
                return;
            }
            list.innerHTML = data.map(n => `
                <div class="flex items-start gap-3 p-3 rounded-2xl hover:bg-gray-50 transition-colors ${!n.is_read ? 'bg-taupe/5' : ''} cursor-pointer mb-1" onclick="readNotif(${n.id}, '${n.link || ''}')">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs
                        ${n.type === 'warning' ? 'bg-yellow-50 text-yellow-600' :
                          n.type === 'success' ? 'bg-green-50 text-green-600' :
                          n.type === 'danger'  ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'}">
                        <i class="fas ${n.type === 'warning' ? 'fa-exclamation-triangle' :
                                         n.type === 'success' ? 'fa-check-circle' :
                                         n.type === 'danger'  ? 'fa-times-circle' : 'fa-info-circle'}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-black text-charcoal leading-tight">${n.title}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5 leading-tight">${n.message}</p>
                        <p class="text-[9px] text-taupe font-bold mt-1" dir="ltr">${n.created_at}</p>
                    </div>
                    ${!n.is_read ? '<div class="w-2 h-2 bg-taupe rounded-full shrink-0 mt-1"></div>' : ''}
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('notifList').innerHTML = '<p class="text-center text-xs text-gray-400 py-8">خطأ في التحميل</p>';
        });
}
function readNotif(id, link) {
    fetch('notif_handler.php?action=mark_read&id=' + id);
    if (link) window.location.href = link;
}
function markAllRead() {
    fetch('notif_handler.php?action=mark_all_read')
        .then(() => { loadNotifications(); document.querySelector('[onclick="toggleNotifications()"] span')?.remove(); });
}

// ─── Offline notice (Capacitor) ──────────────────────────────────
window.addEventListener('aeterna:offline', function() {
    const b = document.createElement('div');
    b.className = 'fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-80 bg-red-600 text-white text-xs font-black px-4 py-3 rounded-2xl shadow-xl z-50 flex items-center gap-3';
    b.innerHTML = '<i class="fas fa-wifi-slash text-base"></i><span>انقطع الاتصال بالإنترنت. حاول مرة أخرى.</span>';
    document.body.appendChild(b);
    setTimeout(() => b.remove(), 5000);
});

// ─── AI Toggle (placeholder) ─────────────────────────────────────
function toggleAI() {
    if(document.getElementById('ai-panel')) {
        document.getElementById('ai-panel').classList.toggle('hidden');
    }
}

// ─── Lenis Smooth Scroll ─────────────────────────────────────────
if(window.Lenis && !window.isCapacitorApp) {
    const lenis = new Lenis({ duration: 1.2, smooth: true });
    function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
    requestAnimationFrame(raf);
}
</script>

<?php if(file_exists('ai_component.php')): ?>
<?php include 'ai_component.php'; ?>
<?php endif; ?>

</body>
</html>
