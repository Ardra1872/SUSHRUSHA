
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role ENUM('patient','caretaker','admin') NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact_number VARCHAR(15),
    emergency_contact VARCHAR(15),
    patient_id INT,
    reset_code VARCHAR(10),
    reset_expiry DATETIME,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE SET NULL
);


CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50),
    compartment_number INT NOT NULL,
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE medicine_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    intake_time TIME NOT NULL,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
);


CREATE TABLE dose_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    patient_id INT NOT NULL,
    intake_datetime DATETIME NOT NULL,
    status ENUM('Taken','Missed') NOT NULL,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE patient_profile (
    patient_id INT PRIMARY KEY,
    dob DATE,
    gender ENUM('Male','Female','Other'),
    blood_group VARCHAR(5),
    height_cm INT,
    weight_kg INT,
    profile_photo VARCHAR(255),
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE medical_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    conditions TEXT,
    allergies TEXT,
    current_medications TEXT,
    doctor_name VARCHAR(100),
    hospital_name VARCHAR(100),
    prescription_file VARCHAR(255),
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE caregivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    caregiver_id INT NOT NULL,
    relation VARCHAR(50),
    notifications_enabled BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (caregiver_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS medicine_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
