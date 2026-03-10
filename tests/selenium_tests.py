import unittest
import time
import random
import string
import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

class SushrushaTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        chrome_options = Options()
        chrome_options.add_argument("--headless")  # Run in headless mode
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1920,1080")
        
        cls.driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
        cls.driver.implicitly_wait(5)
        cls.base_url = "http://localhost/SUSHRUSHA/"
        
        # Random credentials for testing
        cls.test_name = "Testing" + ''.join(random.choices(string.ascii_letters, k=5))
        cls.test_email = "testing_" + ''.join(random.choices(string.digits, k=5)) + "@example.com"
        cls.test_password = "Password123!"
        
        if not os.path.exists("tests/debug"):
            os.makedirs("tests/debug")

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    def save_debug_info(self, name):
        with open(f"tests/debug/{name}.html", "w", encoding="utf-8") as f:
            f.write(self.driver.page_source)
        self.driver.save_screenshot(f"tests/debug/{name}.png")

    def test_01_register(self):
        print(f"\nRunning test_01_register for {self.test_email}...")
        try:
            self.driver.get(self.base_url + "public/register.php")
            print(f"Loaded: {self.driver.current_url} | Title: {self.driver.title}")
            
            wait = WebDriverWait(self.driver, 10)
            name_field = wait.until(EC.presence_of_element_located((By.ID, "name")))
            name_field.send_keys(self.test_name)
            
            self.driver.find_element(By.ID, "email").send_keys(self.test_email)
            self.driver.find_element(By.ID, "password").send_keys(self.test_password)
            
            self.driver.find_element(By.CLASS_NAME, "btn-primary").submit()
            
            # Wait for redirect to login
            wait.until(EC.url_contains("login.php"))
            print("Registration successful.")
        except Exception as e:
            self.save_debug_info("register_fail")
            print(f"Registration failed: {str(e)}")
            raise

    def test_02_login(self):
        print("\nRunning test_02_login...")
        try:
            self.driver.get(self.base_url + "public/login.php")
            print(f"Loaded: {self.driver.current_url} | Title: {self.driver.title}")
            
            wait = WebDriverWait(self.driver, 10)
            email_field = wait.until(EC.presence_of_element_located((By.ID, "email")))
            email_field.send_keys(self.test_email)
            
            self.driver.find_element(By.ID, "password").send_keys(self.test_password)
            self.driver.find_element(By.ID, "loginForm").submit()
            
            # Wait for dashboard
            wait.until(EC.url_contains("dashboard.php"))
            print("Login successful.")
        except Exception as e:
            self.save_debug_info("login_fail")
            print(f"Login failed: {str(e)}")
            raise

    def test_03_add_medicine(self):
        print("\nRunning test_03_add_medicine...")
        try:
            self.driver.get(self.base_url + "src/views/add_medicine.html")
            print(f"Loaded: {self.driver.current_url} | Title: {self.driver.title}")
            
            wait = WebDriverWait(self.driver, 10)
            med_name_input = wait.until(EC.presence_of_element_located((By.ID, "medName")))
            med_name_input.send_keys("Paracetamol")
            
            # Select form
            pill_radio = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, 'input[name="form"][value="Pill"]')))
            self.driver.execute_script("arguments[0].click();", pill_radio) # Use JS click for headless reliability
            
            # Dates
            self.driver.find_element(By.ID, "start_date").send_keys("01-04-2026") # Try different format if needed
            self.driver.find_element(By.ID, "end_date").send_keys("10-04-2026")
            
            # Compartment
            compartment = self.driver.find_element(By.ID, "compartment_number")
            compartment.send_keys("1")
            
            # Notes
            self.driver.find_element(By.ID, "instructions").send_keys("Take after meal")
            
            # Save
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
            self.driver.execute_script("arguments[0].click();", submit_btn)
            
            time.sleep(2)
            print("Add Medicine test completed.")
        except Exception as e:
            self.save_debug_info("add_med_fail")
            print(f"Add medicine failed: {str(e)}")
            raise

    def test_04_profile_update(self):
        print("\nRunning test_04_profile_update...")
        try:
            self.driver.get(self.base_url + "src/views/profile.php")
            print(f"Loaded: {self.driver.current_url} | Title: {self.driver.title}")
            
            wait = WebDriverWait(self.driver, 10)
            dob_input = wait.until(EC.presence_of_element_located((By.NAME, "dob")))
            dob_input.clear()
            dob_input.send_keys("01-01-1990")
            
            self.driver.find_element(By.NAME, "emergency_name").clear()
            self.driver.find_element(By.NAME, "emergency_name").send_keys("Emergency Joe")
            self.driver.find_element(By.NAME, "emergency_phone").clear()
            self.driver.find_element(By.NAME, "emergency_phone").send_keys("1234567890")
            
            save_btn = self.driver.find_element(By.CSS_SELECTOR, '#general button[type="submit"]')
            self.driver.execute_script("arguments[0].click();", save_btn)
            
            # Check for success (either redirect or message)
            time.sleep(2)
            print("Profile update test completed.")
        except Exception as e:
            self.save_debug_info("profile_fail")
            print(f"Profile update failed: {str(e)}")
            raise

if __name__ == "__main__":
    import sys
    test_suite = unittest.TestLoader().loadTestsFromTestCase(SushrushaTest)
    result = unittest.TextTestRunner(verbosity=2).run(test_suite)
    sys.exit(not result.wasSuccessful())
