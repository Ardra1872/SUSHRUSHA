// Reusable Language Selector Component
function initLanguageSelector(containerSelector = 'header') {
  const container = document.querySelector(containerSelector);
  if (!container) return;

  // Check if language selector already exists
  if (document.getElementById('langBtn')) return;

  // Create language selector HTML
  const langSelectorHTML = `
    <div class="relative" id="langSelectorContainer">
      <button id="langBtn" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
        🌐
      </button>
      <div id="langMenu" class="hidden absolute right-0 top-full mt-2 w-40 bg-white dark:bg-[#101922] border border-slate-200 dark:border-slate-800 rounded-lg shadow-lg z-50">
        <button class="lang-option block w-full text-left px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors" data-lang="en">English</button>
        <button class="lang-option block w-full text-left px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors" data-lang="ml">Malayalam</button>
      </div>
    </div>
  `;

  // Find the right place to insert (usually in header actions area)
  const headerActions = container.querySelector('.flex.items-center.gap-4, .flex.items-center.gap-3, [class*="flex"][class*="items-center"]');
  if (headerActions) {
    headerActions.insertAdjacentHTML('beforeend', langSelectorHTML);
  } else {
    // Fallback: insert at end of container
    container.insertAdjacentHTML('beforeend', langSelectorHTML);
  }

  // Initialize language selector functionality
  const langBtn = document.getElementById("langBtn");
  const langMenu = document.getElementById("langMenu");
  const langOptions = document.querySelectorAll(".lang-option");

  if (langBtn && langMenu) {
    langBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      langMenu.classList.toggle("hidden");
    });

    document.addEventListener("click", (e) => {
      if (!langMenu.contains(e.target) && !langBtn.contains(e.target)) {
        langMenu.classList.add("hidden");
      }
    });

    langOptions.forEach(btn => {
      btn.addEventListener("click", async () => {
        langMenu.classList.add("hidden");
        if (typeof translationSystem !== 'undefined') {
          await translationSystem.translatePage(btn.dataset.lang);
        }
      });
    });
  }
}

// Initialize translation system on page load
function initTranslationSystem() {
  if (typeof translationSystem !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener("DOMContentLoaded", () => {
        translationSystem.init();
      });
    } else {
      translationSystem.init();
    }
  }
}
