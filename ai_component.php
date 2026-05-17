<!-- ai_component.php - واجهة المساعد الذكي -->
<?php
// جلب أيقونة النظام لاستخدامها في الشات
$ai_icon = getSetting('favicon_path', 'uploads/assets/icon-192.png');
?>
<style>
/* تخصيص الاسكرول بار الخاص بالشات ليكون ناعم وواضح */
.ai-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(124, 58, 237, 0.3) transparent;
    overscroll-behavior: contain; 
}
.ai-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.ai-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.ai-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(124, 58, 237, 0.3);
    border-radius: 10px;
}
.ai-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: rgba(124, 58, 237, 0.6);
}

/* أنيميشن الأيقونة */
@keyframes avatarPulse {
    0% { box-shadow: 0 0 0 0 rgba(168, 85, 247, 0.4); }
    70% { box-shadow: 0 0 0 6px rgba(168, 85, 247, 0); }
    100% { box-shadow: 0 0 0 0 rgba(168, 85, 247, 0); }
}
.ai-avatar-pulse { animation: avatarPulse 2s infinite ease-in-out; }

@keyframes logoFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-3px) rotate(5deg); }
}
.ai-logo-anim { animation: logoFloat 4s infinite ease-in-out; }
</style>

<!-- نافذة الشات -->
<div id="aiChatWindow" class="fixed bottom-[95px] lg:bottom-24 left-4 right-4 sm:left-6 sm:right-auto w-auto sm:w-[420px] bg-white/95 backdrop-blur-2xl border border-purple-100 rounded-3xl shadow-[0_15px_50px_rgba(124,58,237,0.15)] z-[150] transform scale-0 origin-bottom-left opacity-0 transition-all duration-300 flex flex-col overflow-hidden max-h-[85vh]">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-[#7c3aed] to-[#db2777] p-4 flex justify-between items-center text-white shadow-md z-10 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center p-1.5 ai-avatar-pulse overflow-hidden">
                <img src="<?= htmlspecialchars($ai_icon) ?>" class="w-full h-full object-contain ai-logo-anim" alt="AI">
            </div>
            <div>
                <h3 class="font-black text-sm tracking-tight">Aeterna AI</h3>
                <p class="text-[9px] text-white/80 font-bold uppercase tracking-wider">مساعدك الذكي الشخصي</p>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <button onclick="confirmResetChat()" title="بدء محادثة جديدة" class="text-white/70 hover:text-white hover:bg-white/10 w-8 h-8 rounded-full transition-colors flex items-center justify-center">
                <i class="fas fa-redo-alt text-xs"></i>
            </button>
            <button onclick="toggleAiChat()" class="text-white/70 hover:text-white hover:bg-white/10 w-8 h-8 rounded-full transition-colors flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Chat Body (Scrollable Area) -->
    <div id="aiChatBody" class="flex-1 p-4 overflow-y-auto ai-scrollbar space-y-4 bg-gray-50/50 min-h-[350px]">
        
        <div id="aiInitialState" class="space-y-4">
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-white border border-purple-100 flex items-center justify-center p-1 flex-shrink-0 shadow-sm">
                    <img src="<?= htmlspecialchars($ai_icon) ?>" class="w-full h-full object-contain" alt="AI">
                </div>
                <div class="bg-white p-3.5 rounded-2xl rounded-tr-none shadow-sm border border-gray-100 text-sm text-charcoal leading-relaxed font-medium">
                    أهلاً بيك! أنا المساعد الذكي للنظام ⚡️ <br>
                    أقدر أبحث في الداتابيز، أحلل الأرباح والمصروفات، أو أجاوبك على أي سؤال. إزاي أساعدك النهاردة؟
                </div>
            </div>

            <div id="aiSuggestionsGrid" class="grid grid-cols-2 gap-2 mt-4 animate__animated animate__fadeInUp">
                <!-- المطابقة -->
                <a href="ai_reconcile.php" class="flex items-center gap-2 bg-gradient-to-br from-purple-600 to-pink-600 p-2.5 rounded-2xl hover:opacity-90 transition-all text-right group col-span-2 shadow-lg mb-2">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white"><i class="fas fa-file-invoice text-sm"></i></div>
                    <span class="text-[12px] font-black text-white flex-1">مطابقة كشوف الحساب بالذكاء الاصطناعي ✨</span>
                </a>

                <!-- أزرار الاختصارات الجديدة والشاملة -->
                <button onclick="setAndSendAi('كم إجمالي المقبوضات (إيرادات) اليوم؟')" class="flex items-center gap-2 bg-purple-50/50 border border-purple-100 p-2.5 rounded-2xl hover:bg-purple-100 transition-colors text-right group">
                    <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center shadow-sm text-purple-600 group-hover:scale-110 transition-transform"><i class="fas fa-chart-line text-xs"></i></div>
                    <span class="text-[11px] font-bold text-gray-700 flex-1">مبيعات اليوم</span>
                </button>
                <button onclick="setAndSendAi('مين العملاء المتأخرين في الدفع؟')" class="flex items-center gap-2 bg-red-50/50 border border-red-100 p-2.5 rounded-2xl hover:bg-red-100 transition-colors text-right group">
                    <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center shadow-sm text-red-500 group-hover:scale-110 transition-transform"><i class="fas fa-clock text-xs"></i></div>
                    <span class="text-[11px] font-bold text-gray-700 flex-1">دفعات متأخرة</span>
                </button>
                <button onclick="setAndSendAi('إيه المشاريع اللي لسه شغالة ومين العملاء بتوعها؟')" class="flex items-center gap-2 bg-blue-50/50 border border-blue-100 p-2.5 rounded-2xl hover:bg-blue-100 transition-colors text-right group">
                    <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center shadow-sm text-blue-500 group-hover:scale-110 transition-transform"><i class="fas fa-project-diagram text-xs"></i></div>
                    <span class="text-[11px] font-bold text-gray-700 flex-1">مشاريع جارية</span>
                </button>
                <button onclick="setAndSendAi('مين من الموظفين حاضر ومين إجازة النهاردة؟')" class="flex items-center gap-2 bg-emerald-50/50 border border-emerald-100 p-2.5 rounded-2xl hover:bg-emerald-100 transition-colors text-right group">
                    <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center shadow-sm text-emerald-500 group-hover:scale-110 transition-transform"><i class="fas fa-users text-xs"></i></div>
                    <span class="text-[11px] font-bold text-gray-700 flex-1">حضور وإجازات اليوم</span>
                </button>
                <button onclick="setAndSendAi('إيه أحدث تقارير الموقع اللي اترفعت؟')" class="flex items-center gap-2 bg-amber-50/50 border border-amber-100 p-2.5 rounded-2xl hover:bg-amber-100 transition-colors text-right group">
                    <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center shadow-sm text-amber-500 group-hover:scale-110 transition-transform"><i class="fas fa-hard-hat text-xs"></i></div>
                    <span class="text-[11px] font-bold text-gray-700 flex-1">آخر تقارير الموقع</span>
                </button>
                <button onclick="setAndSendAi('إيه المهام اللي لسه مخلصتش في المشاريع؟')" class="flex items-center gap-2 bg-indigo-50/50 border border-indigo-100 p-2.5 rounded-2xl hover:bg-indigo-100 transition-colors text-right group">
                    <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center shadow-sm text-indigo-500 group-hover:scale-110 transition-transform"><i class="fas fa-tasks text-xs"></i></div>
                    <span class="text-[11px] font-bold text-gray-700 flex-1">المهام المعلقة</span>
                </button>
            </div>
        </div>

        <div id="aiDynamicMessages" class="space-y-4"></div>

    </div>

    <!-- Input Area -->
    <div class="p-3 bg-white border-t border-gray-100 shrink-0 pb-safe relative">
        <form id="aiChatForm" onsubmit="handleAiSubmit(event)" class="relative flex items-center">
            <input type="text" id="aiQueryInput" placeholder="اسأل عن مشروع، عميل، أرقام، حضور..." autocomplete="off" class="w-full bg-gray-50 border border-gray-200 rounded-full py-3.5 px-4 pr-12 text-[16px] sm:text-sm focus:ring-2 focus:ring-purple-400 focus:border-purple-400 outline-none transition-all shadow-inner font-medium text-charcoal">
            <button type="submit" id="aiSendBtn" class="absolute right-2 w-9 h-9 bg-gradient-to-tr from-purple-600 to-pink-500 hover:opacity-90 text-white rounded-full flex items-center justify-center transition-transform hover:scale-105 active:scale-95 shadow-md">
                <i class="fas fa-paper-plane text-xs relative -left-0.5"></i>
            </button>
        </form>
        <div class="text-center mt-2">
            <span class="text-[9px] text-gray-400 font-bold tracking-tight">⚡️ Aeterna AI — المحادثات محمية وصلاحياتك مطبقة</span>
        </div>
    </div>
</div>

<script>
const aiWindow = document.getElementById('aiChatWindow');
const aiBody = document.getElementById('aiChatBody');
const aiInput = document.getElementById('aiQueryInput');
const initialState = document.getElementById('aiInitialState');
const dynamicMessages = document.getElementById('aiDynamicMessages');
const aiIconUrl = "<?= htmlspecialchars($ai_icon) ?>";

function toggleAiChat() {
    if (aiWindow.classList.contains('scale-0')) {
        aiWindow.classList.remove('scale-0', 'opacity-0');
        aiWindow.classList.add('scale-100', 'opacity-100');
        localStorage.setItem('aeterna_ai_open', 'true');
        setTimeout(() => { aiInput.focus(); scrollToBottom(); }, 100);
    } else {
        aiWindow.classList.add('scale-0', 'opacity-0');
        aiWindow.classList.remove('scale-100', 'opacity-100');
        localStorage.setItem('aeterna_ai_open', 'false');
    }
}

async function confirmResetChat() {
    if (!confirm('هل تريد مسح المحادثة والبدء من جديد؟')) return;
    try {
        await fetch('ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: 'امسح المحادثة' })
        });
        dynamicMessages.innerHTML = '';
        initialState.style.display = 'block';
        aiInput.value = '';
    } catch (e) { location.reload(); }
}

document.addEventListener('DOMContentLoaded', async () => {
    if (localStorage.getItem('aeterna_ai_open') === 'true') {
        aiWindow.classList.remove('scale-0', 'opacity-0', 'duration-300');
        aiWindow.classList.add('scale-100', 'opacity-100');
        setTimeout(() => { aiWindow.classList.add('duration-300'); scrollToBottom(); }, 50);
    }

    try {
        const response = await fetch('ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_history' })
        });
        const textResponse = await response.text();
        
        const jsonMatch = textResponse.match(/\{[\s\S]*\}/);
        if(jsonMatch) {
            const data = JSON.parse(jsonMatch[0]);
            if (data.history && data.history.length > 0) {
                initialState.style.display = 'none';
                data.history.forEach(msg => {
                    appendMessage(msg.content, msg.role === 'user' ? 'user' : 'ai', true);
                });
            }
        }
    } catch (e) {}
});

function setAndSendAi(text) {
    aiInput.value = text;
    initialState.style.display = 'none';
    document.getElementById('aiSendBtn').click();
}

function handleAiSubmit(e) {
    e.preventDefault();
    const query = aiInput.value.trim();
    if (!query) return;
    initialState.style.display = 'none';
    sendAiMessage(query);
}

async function sendAiMessage(query) {
    appendMessage(query, 'user');
    aiInput.value = ''; aiInput.disabled = true;
    const loadingId = appendLoading();

    try {
        const response = await fetch('ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: query })
        });
        
        const rawText = await response.text(); 
        
        let data;
        try {
            const jsonMatch = rawText.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                data = JSON.parse(jsonMatch[0]);
            } else {
                throw new Error("No JSON structure found");
            }
        } catch (jsonError) {
            console.error("الرد مش JSON:", rawText);
            let cleanError = rawText.replace(/(<([^>]+)>)/gi, "").trim();
            if(cleanError.length > 150) cleanError = cleanError.substring(0, 150) + '...';
            throw new Error(cleanError || 'السيرفر مرجعش بيانات.');
        }

        const loadEl = document.getElementById(loadingId);
        if (loadEl) loadEl.remove();

        if (data && data.response) {
            appendMessage(data.response, 'ai');
        } else {
            appendMessage('⚠️ الخادم مقدرش يعالج الطلب بشكل صحيح.', 'ai');
        }

        if (query.includes('امسح المحادثة') || query.includes('ابدأ من جديد')) {
            setTimeout(() => { dynamicMessages.innerHTML = ''; initialState.style.display = 'block'; }, 1000);
        }
    } catch (error) {
        const loadEl = document.getElementById(loadingId);
        if (loadEl) loadEl.remove();
        console.error("مشكلة في الاتصال أو المعالجة:", error);
        appendMessage(`⚠️ عذراً، حصلت مشكلة: ${error.message}`, 'ai');
    }
    
    aiInput.disabled = false; 
    aiInput.focus();
}

function appendMessage(text, sender, skipAnimation = false) {
    const msgDiv = document.createElement('div');
    const animClass = skipAnimation ? '' : 'animate__animated animate__fadeInUp animate__faster';
    msgDiv.className = sender === 'user' ? `flex gap-3 flex-row-reverse ${animClass}` : `flex gap-3 ${animClass}`;
    
    let avatar = sender === 'user' ? 
        `<div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 flex-shrink-0 shadow-sm"><i class="fas fa-user text-xs"></i></div>` :
        `<div class="w-8 h-8 rounded-full bg-white border border-purple-100 flex items-center justify-center p-1 flex-shrink-0 shadow-sm overflow-hidden"><img src="${aiIconUrl}" class="w-full h-full object-contain" alt="AI"></div>`;

    let bubbleClass = sender === 'user' ? 'bg-charcoal text-white p-3.5 rounded-2xl rounded-tl-none shadow-md text-sm font-medium' : 'bg-white p-3.5 rounded-2xl rounded-tr-none shadow-sm border border-gray-100 text-sm text-charcoal leading-relaxed font-medium';

    msgDiv.innerHTML = `${avatar}<div class="${bubbleClass}" style="word-break: break-word;">${text}</div>`;
    dynamicMessages.appendChild(msgDiv);
    scrollToBottom();
}

function appendLoading() {
    const id = 'loading-' + Date.now();
    const msgDiv = document.createElement('div');
    msgDiv.id = id;
    msgDiv.className = 'flex gap-3 animate__animated animate__fadeInUp animate__faster';
    msgDiv.innerHTML = `
        <div class="w-8 h-8 rounded-full bg-white border border-purple-100 flex items-center justify-center p-1 flex-shrink-0 shadow-sm overflow-hidden ai-avatar-pulse"><img src="${aiIconUrl}" class="w-full h-full object-contain ai-logo-anim" alt="AI"></div>
        <div class="bg-white p-3.5 rounded-2xl rounded-tr-none shadow-sm border border-gray-100 flex gap-1.5 items-center h-11">
            <div class="w-2 h-2 bg-purple-400 rounded-full animate-bounce"></div>
            <div class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
            <div class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
        </div>`;
    dynamicMessages.appendChild(msgDiv);
    scrollToBottom();
    return id;
}

function scrollToBottom() { aiBody.scrollTo({ top: aiBody.scrollHeight, behavior: 'smooth' }); }
</script>