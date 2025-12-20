
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
