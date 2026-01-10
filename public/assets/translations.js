// Translation System for Sushrusha
class TranslationSystem {
  constructor() {
    this.currentLang = localStorage.getItem("siteLang") || "en";
    this.translationCache = {};
    this.translations = {
      en: {
        // Profile Page
        "general_profile": "General Profile",
        "manage_personal_info": "Manage your personal information, medical ID, and emergency contacts.",
        "full_name": "Full Name",
        "date_of_birth": "Date of Birth",
        "emergency_contact": "Emergency Contact",
        "contact_name_phone": "Contact Name & Phone",
        "change_photo": "Change Photo",
        "remove": "Remove",
        "cancel": "Cancel",
        "save_changes": "Save Changes",
        "medical_details": "Medical Details",
        "update_medical_info": "Update your basic medical information.",
        "gender": "Gender",
        "select_gender": "Select Gender",
        "male": "Male",
        "female": "Female",
        "other": "Other",
        "blood_group": "Blood Group",
        "select_blood_group": "Select Blood Group",
        "height_cm": "Height (cm)",
        "weight_kg": "Weight (kg)",
        "save_medical_details": "Save Medical Details",
        "security_login": "Security & Login",
        "update_password_secure": "Update your password and secure your account.",
        "current_password": "Current Password",
        "new_password": "New Password",
        "confirm_password": "Confirm Password",
        "update_password": "Update Password",
        "notifications": "Notifications",
        "choose_alerts": "Choose how you want to be alerted for medications.",
        "medication_reminders": "Medication Reminders",
        "daily_alerts": "Daily alerts when it's time to take your pills",
        "refill_alerts": "Refill Alerts",
        "notified_low": "Get notified when inventory is low (below 10%)",
        "email_digest": "Email Digest",
        "weekly_report": "Weekly adherence report",
        "connected_devices": "Connected Devices",
        "manage_dispensers": "Manage your smart dispensers and wearables.",
        "add_device": "Add Device",
        "settings": "Settings",
        "account": "Account",
        "device_management": "Device Management",
        "my_devices": "My Devices",
        "care_team_access": "Care Team Access",
        // Dashboard
        "dashboard": "Dashboard",
        "my_medicines": "My Medicines",
        "prescriptions": "Prescriptions",
        "assign_caretaker": "Assign Caretaker",
        "alerts": "Alerts",
        "medicine_requests": "Medicine Requests",
        "add_medicine": "Add Medicine",
        "logout": "Logout",
        "dashboard_overview": "Dashboard Overview",
        "dashboard_overview_text": "Here's your health overview for today.",
        "adherence": "Adherence",
        "missed_doses": "Missed Doses",
        "next_refill": "Next Refill",
        "search_medicines": "Search medicines...",
        "assign_caretaker_text": "Add a trusted caretaker who can help manage your medicines.",
        "caretaker_name": "Name",
        "relation_placeholder": "Select relation",
        "assign_caretaker_btn": "Assign Caretaker",
        // Caretaker Dashboard
        "caretaker_dashboard": "Caretaker Dashboard",
        "patient_list": "Patient List",
        "schedule": "Schedule",
        "select_patient": "Select a patient to view their schedule",
        "no_patients": "No patients assigned",
        // Add Medicine
        "add_new_medicine": "Add New Medicine",
        "medicine_name": "Medicine Name",
        "dosage": "Dosage",
        "form": "Form",
        "pill": "Pill",
        "tablet": "Tablet",
        "capsule": "Capsule",
        "liquid": "Liquid",
        "injection": "Injection",
        "reminder_mode": "Reminder Mode",
        "fixed_time": "Fixed Time",
        "interval": "Interval",
        "intake_times": "Intake Times",
        "add_time": "Add Time",
        "start_date": "Start Date",
        "end_date": "End Date",
        "save_medicine": "Save Medicine",
        // Common
        "submit": "Submit",
        "delete": "Delete",
        "edit": "Edit",
        "close": "Close",
        "loading": "Loading...",
        "error": "Error",
        "success": "Success",
        "save": "Save",
        "back": "Back",
        "next": "Next",
        "previous": "Previous",
        "yes": "Yes",
        "no": "No",
        "confirm": "Confirm",
        // Login & Register
        "welcome_back": "Welcome Back!",
        "login_subtitle": "Login to access your medicine reminders and care dashboard",
        "email": "Email",
        "password": "Password",
        "enter_email": "Enter your email...",
        "enter_password": "Enter your password",
        "forgot_password": "Forgot your password?",
        "login": "Login",
        "continue_with_google": "Continue with Google",
        "dont_have_account": "Don't have an account?",
        "create_account": "Create Account",
        "create_account_title": "Create Account",
        "register_subtitle": "Start managing your medicine schedule today",
        "full_name_placeholder": "Full Name",
        "email_placeholder": "Email",
        "password_placeholder": "Password",
        "register": "Register",
        "already_have_account": "Already have an account?",
        // Dashboard
        "dashboard": "Dashboard",
        "my_medicines": "My Medicines",
        "prescriptions": "Prescriptions",
        "assign_caretaker": "Assign Caretaker",
        "alerts": "Alerts",
        "medicine_requests": "Medicine Requests",
        "add_medicine": "Add Medicine",
        "logout": "Logout",
        "dashboard_overview": "Dashboard Overview",
        "dashboard_overview_text": "Here's your health overview for today.",
        "adherence": "Adherence",
        "missed_doses": "Missed Doses",
        "next_refill": "Next Refill",
        "assign_caretaker_text": "Add a trusted caretaker who can help manage your medicines.",
        "caretaker_name": "Name",
        "relation_placeholder": "Select relation",
        "assign_caretaker_btn": "Assign Caretaker",
        "search_medicines": "Search medicines..."
      },
      ml: {
        // Profile Page - Malayalam
        "general_profile": "പൊതു പ്രൊഫൈൽ",
        "manage_personal_info": "നിങ്ങളുടെ വ്യക്തിഗത വിവരങ്ങൾ, മെഡിക്കൽ ഐഡി, അടിയന്തിര കോൺടാക്റ്റുകൾ നിയന്ത്രിക്കുക.",
        "full_name": "പൂർണ്ണ നാമം",
        "date_of_birth": "ജനന തീയതി",
        "emergency_contact": "അടിയന്തിര കോൺടാക്റ്റ്",
        "contact_name_phone": "കോൺടാക്റ്റ് പേരും ഫോണും",
        "change_photo": "ഫോട്ടോ മാറ്റുക",
        "remove": "നീക്കം ചെയ്യുക",
        "cancel": "റദ്ദാക്കുക",
        "save_changes": "മാറ്റങ്ങൾ സംരക്ഷിക്കുക",
        "medical_details": "മെഡിക്കൽ വിവരങ്ങൾ",
        "update_medical_info": "നിങ്ങളുടെ അടിസ്ഥാന മെഡിക്കൽ വിവരങ്ങൾ അപ്ഡേറ്റ് ചെയ്യുക.",
        "gender": "ലിംഗം",
        "select_gender": "ലിംഗം തിരഞ്ഞെടുക്കുക",
        "male": "പുരുഷൻ",
        "female": "സ്ത്രീ",
        "other": "മറ്റുള്ളവ",
        "blood_group": "രക്തഗ്രൂപ്പ്",
        "select_blood_group": "രക്തഗ്രൂപ്പ് തിരഞ്ഞെടുക്കുക",
        "height_cm": "ഉയരം (സെ.മീ)",
        "weight_kg": "ഭാരം (കി.ഗ്രാം)",
        "save_medical_details": "മെഡിക്കൽ വിവരങ്ങൾ സംരക്ഷിക്കുക",
        "security_login": "സുരക്ഷയും ലോഗിൻ",
        "update_password_secure": "നിങ്ങളുടെ പാസ്‌വേഡ് അപ്ഡേറ്റ് ചെയ്ത് അക്കൗണ്ട് സുരക്ഷിതമാക്കുക.",
        "current_password": "നിലവിലെ പാസ്‌വേഡ്",
        "new_password": "പുതിയ പാസ്‌വേഡ്",
        "confirm_password": "പാസ്‌വേഡ് സ്ഥിരീകരിക്കുക",
        "update_password": "പാസ്‌വേഡ് അപ്ഡേറ്റ് ചെയ്യുക",
        "notifications": "അറിയിപ്പുകൾ",
        "choose_alerts": "മരുന്നുകൾക്കായി നിങ്ങൾക്ക് എങ്ങനെ അറിയിക്കണമെന്ന് തിരഞ്ഞെടുക്കുക.",
        "medication_reminders": "മരുന്ന് ഓർമ്മപ്പെടുത്തലുകൾ",
        "daily_alerts": "നിങ്ങളുടെ ഗുളികകൾ കഴിക്കേണ്ട സമയത്ത് ദൈനംദിന അറിയിപ്പുകൾ",
        "refill_alerts": "റീഫിൽ അറിയിപ്പുകൾ",
        "notified_low": "ഇൻവെന്ററി കുറവാകുമ്പോൾ (10% ൽ താഴെ) അറിയിക്കുക",
        "email_digest": "ഇമെയിൽ സംഗ്രഹം",
        "weekly_report": "വാർഷിക പാലന റിപ്പോർട്ട്",
        "connected_devices": "കണക്റ്റുചെയ്ത ഉപകരണങ്ങൾ",
        "manage_dispensers": "നിങ്ങളുടെ സ്മാർട്ട് ഡിസ്പെൻസറുകളും വിയറബിളുകളും നിയന്ത്രിക്കുക.",
        "add_device": "ഉപകരണം ചേർക്കുക",
        "settings": "ക്രമീകരണങ്ങൾ",
        "account": "അക്കൗണ്ട്",
        "device_management": "ഉപകരണ നിയന്ത്രണം",
        "my_devices": "എന്റെ ഉപകരണങ്ങൾ",
        "care_team_access": "കെയർ ടീം ആക്സസ്",
        // Dashboard - Malayalam
        "dashboard": "ഡാഷ്‌ബോർഡ്",
        "my_medicines": "എന്റെ മരുന്നുകൾ",
        "prescriptions": "പ്രെസ്‌ക്രിപ്ഷനുകൾ",
        "assign_caretaker": "കെയർടേക്കർ നിയോഗിക്കുക",
        "alerts": "അറിയിപ്പുകൾ",
        "medicine_requests": "മരുന്ന് അഭ്യർത്ഥനകൾ",
        "add_medicine": "മരുന്ന് ചേർക്കുക",
        "logout": "ലോഗൗട്ട്",
        "dashboard_overview": "ഡാഷ്‌ബോർഡ് അവലോകനം",
        "dashboard_overview_text": "ഇന്നത്തെ നിങ്ങളുടെ ആരോഗ്യ അവലോകനം ഇതാ.",
        "adherence": "പാലനം",
        "missed_doses": "എഴുത്തുകൾ നഷ്ടപ്പെട്ടു",
        "next_refill": "അടുത്ത റീഫിൽ",
        "search_medicines": "മരുന്നുകൾ തിരയുക...",
        "assign_caretaker_text": "നിങ്ങളുടെ മരുന്നുകൾ നിയന്ത്രിക്കാൻ സഹായിക്കാൻ കഴിയുന്ന വിശ്വസനീയമായ ഒരു കെയർടേക്കർ ചേർക്കുക.",
        "caretaker_name": "പേര്",
        "relation_placeholder": "ബന്ധം തിരഞ്ഞെടുക്കുക",
        "assign_caretaker_btn": "കെയർടേക്കർ നിയോഗിക്കുക",
        // Caretaker Dashboard - Malayalam
        "caretaker_dashboard": "കെയർടേക്കർ ഡാഷ്‌ബോർഡ്",
        "patient_list": "രോഗി പട്ടിക",
        "schedule": "പട്ടിക",
        "select_patient": "അവരുടെ പട്ടിക കാണാൻ ഒരു രോഗിയെ തിരഞ്ഞെടുക്കുക",
        "no_patients": "രോഗികൾ നിയോഗിച്ചിട്ടില്ല",
        // Add Medicine - Malayalam
        "add_new_medicine": "പുതിയ മരുന്ന് ചേർക്കുക",
        "medicine_name": "മരുന്നിന്റെ പേര്",
        "dosage": "ഡോസേജ്",
        "form": "രൂപം",
        "pill": "ഗുളിക",
        "tablet": "ടാബ്ലെറ്റ്",
        "capsule": "കാപ്‌സ്യൂൾ",
        "liquid": "ദ്രാവകം",
        "injection": "ഇഞ്ചക്ഷൻ",
        "reminder_mode": "റിമൈൻഡർ മോഡ്",
        "fixed_time": "നിശ്ചിത സമയം",
        "interval": "ഇടവേള",
        "intake_times": "കഴിക്കേണ്ട സമയങ്ങൾ",
        "add_time": "സമയം ചേർക്കുക",
        "start_date": "ആരംഭ തീയതി",
        "end_date": "അവസാന തീയതി",
        "save_medicine": "മരുന്ന് സംരക്ഷിക്കുക",
        // Common - Malayalam
        "submit": "സമർപ്പിക്കുക",
        "delete": "ഇല്ലാതാക്കുക",
        "edit": "എഡിറ്റ് ചെയ്യുക",
        "close": "അടയ്ക്കുക",
        "loading": "ലോഡ് ചെയ്യുന്നു...",
        "error": "പിശക്",
        "success": "വിജയം",
        "save": "സംരക്ഷിക്കുക",
        "back": "തിരികെ",
        "next": "അടുത്തത്",
        "previous": "മുമ്പത്തെ",
        "yes": "അതെ",
        "no": "ഇല്ല",
        "confirm": "സ്ഥിരീകരിക്കുക",
        // Login & Register - Malayalam
        "welcome_back": "വീണ്ടും സ്വാഗതം!",
        "login_subtitle": "നിങ്ങളുടെ മരുന്ന് ഓർമ്മപ്പെടുത്തലുകളും കെയർ ഡാഷ്‌ബോർഡും ആക്സസ് ചെയ്യാൻ ലോഗിൻ ചെയ്യുക",
        "email": "ഇമെയിൽ",
        "password": "പാസ്‌വേഡ്",
        "enter_email": "നിങ്ങളുടെ ഇമെയിൽ നൽകുക...",
        "enter_password": "നിങ്ങളുടെ പാസ്‌വേഡ് നൽകുക",
        "forgot_password": "പാസ്‌വേഡ് മറന്നോ?",
        "login": "ലോഗിൻ",
        "continue_with_google": "Google-ൽ തുടരുക",
        "dont_have_account": "അക്കൗണ്ട് ഇല്ലേ?",
        "create_account": "അക്കൗണ്ട് സൃഷ്ടിക്കുക",
        "create_account_title": "അക്കൗണ്ട് സൃഷ്ടിക്കുക",
        "register_subtitle": "ഇന്ന് നിങ്ങളുടെ മരുന്ന് ഷെഡ്യൂൾ നിയന്ത്രിക്കാൻ ആരംഭിക്കുക",
        "full_name_placeholder": "പൂർണ്ണ നാമം",
        "email_placeholder": "ഇമെയിൽ",
        "password_placeholder": "പാസ്‌വേഡ്",
        "register": "രജിസ്റ്റർ",
        "already_have_account": "ഇതിനകം അക്കൗണ്ട് ഉണ്ടോ?",
        // Dashboard - Malayalam
        "dashboard": "ഡാഷ്‌ബോർഡ്",
        "my_medicines": "എന്റെ മരുന്നുകൾ",
        "prescriptions": "പ്രെസ്ക്രിപ്ഷനുകൾ",
        "assign_caretaker": "കെയർടേക്കർ നിയോഗിക്കുക",
        "alerts": "അറിയിപ്പുകൾ",
        "medicine_requests": "മരുന്ന് അഭ്യർത്ഥനകൾ",
        "add_medicine": "മരുന്ന് ചേർക്കുക",
        "logout": "ലോഗൗട്ട്",
        "dashboard_overview": "ഡാഷ്‌ബോർഡ് അവലോകനം",
        "dashboard_overview_text": "ഇന്നത്തെ നിങ്ങളുടെ ആരോഗ്യ അവലോകനം ഇതാ.",
        "adherence": "പാലനം",
        "missed_doses": "എഴുത്തുകൾ നഷ്ടപ്പെട്ടു",
        "next_refill": "അടുത്ത റീഫിൽ",
        "assign_caretaker_text": "നിങ്ങളുടെ മരുന്നുകൾ നിയന്ത്രിക്കാൻ സഹായിക്കാൻ കഴിയുന്ന വിശ്വസനീയമായ ഒരു കെയർടേക്കർ ചേർക്കുക.",
        "caretaker_name": "പേര്",
        "relation_placeholder": "ബന്ധം തിരഞ്ഞെടുക്കുക",
        "assign_caretaker_btn": "കെയർടേക്കർ നിയോഗിക്കുക",
        "search_medicines": "മരുന്നുകൾ തിരയുക..."
      }
    };
  }

  async translateText(text, targetLang) {
    if (targetLang === "en") return text;
    
    const cacheKey = text + "|" + targetLang;
    if (this.translationCache[cacheKey]) {
      return this.translationCache[cacheKey];
    }

    // First check if we have a direct translation
    const directKey = Object.keys(this.translations.en).find(
      key => this.translations.en[key] === text.trim()
    );
    
    if (directKey && this.translations[targetLang] && this.translations[targetLang][directKey]) {
      this.translationCache[cacheKey] = this.translations[targetLang][directKey];
      return this.translations[targetLang][directKey];
    }

    // Fallback to API translation
    try {
      const res = await fetch("https://libretranslate.de/translate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          q: text,
          source: "en",
          target: targetLang,
          format: "text"
        })
      });

      const data = await res.json();
      this.translationCache[cacheKey] = data.translatedText;
      return data.translatedText;
    } catch (error) {
      console.error("Translation error:", error);
      return text;
    }
  }

  async translatePage(lang) {
    this.currentLang = lang;
    localStorage.setItem("siteLang", lang);

    // Translate elements with data-i18n
    const elements = document.querySelectorAll("[data-i18n]");
    for (const el of elements) {
      const key = el.getAttribute("data-i18n");
      if (this.translations[lang] && this.translations[lang][key]) {
        el.textContent = this.translations[lang][key];
      } else {
        el.textContent = await this.translateText(el.textContent.trim(), lang);
      }
    }

    // Translate placeholders
    const placeholders = document.querySelectorAll("[data-i18n-placeholder]");
    for (const el of placeholders) {
      const placeholder = el.getAttribute("placeholder");
      if (placeholder) {
        el.setAttribute(
          "placeholder",
          await this.translateText(placeholder, lang)
        );
      }
    }

    // Translate option text in selects
    const selects = document.querySelectorAll("select[data-i18n-options]");
    for (const select of selects) {
      const options = select.querySelectorAll("option");
      for (const option of options) {
        if (option.value && option.textContent.trim()) {
          option.textContent = await this.translateText(option.textContent.trim(), lang);
        }
      }
    }
  }

  init() {
    // Initialize language on page load
    if (this.currentLang !== "en") {
      this.translatePage(this.currentLang);
    }
  }
}

// Global instance
const translationSystem = new TranslationSystem();
