<?php
include '../php/profile_update.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="/css/update_pofile.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>
    <div class="container">
        <?php include 'sidebar.html'; ?>
        <div class="content">
            <h1 class="update_profile-form">PATIENT PROFILE</h1>
            <hr class="thin-line">
            <?php if (isset($error_message)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <form class="update_profile-form" id="updateProfileForm" action="../php/profile_update.php" method="POST">
                <div class="patient-profile-container">
                    <div class="patient-profile">
                        <label class="label" for="last_name">LAST NAME</label>
                        <input name="last_name" id="last_name" class="input" type="text"
                            value="<?= htmlspecialchars($last_name); ?>" required>
                    </div>
                    <div class="patient-profile">
                        <label class="label" for="first_name">FIRST NAME</label>
                        <input name="first_name" id="first_name" class="input" type="text"
                            value="<?= htmlspecialchars($first_name); ?>" required>
                    </div>
                    <div class="patient-profile">
                        <label class="label" for="middle_initial">MIDDLE INITIAL</label>
                        <input name="middle_initial" id="middle_initial" class="input" type="text"
                            value="<?= htmlspecialchars($middle_initial); ?>" required>
                    </div>
                </div>

                <div class="patient-profile-container">
                    <div class="patient-profile1">
                        <label class="label" for="date_of_birth">DATE OF BIRTH</label>
                        <input name="date_of_birth" id="date_of_birth" class="input1" type="date"
                            value="<?= htmlspecialchars($date_of_birth); ?>">
                    </div>
                    <div class="patient-profile1">
                        <label class="label" for="gender">GENDER</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="" disabled <?= isset($gender) && empty($gender) ? 'selected' : ''; ?>>Select
                                Gender
                            </option>
                            <option value="Male" <?= htmlspecialchars($gender) == 'Male' ? 'selected' : ''; ?>>Male
                            </option>
                            <option value="Female" <?= htmlspecialchars($gender) == 'Female' ? 'selected' : ''; ?>>Female
                            </option>
                            <option value="Other" <?= htmlspecialchars($gender) == 'Other' ? 'selected' : ''; ?>>Other
                            </option>
                        </select>
                    </div>
                    <div class="patient-profile1">
                        <label class="user-label" for="age">ENTER AGE</label>
                        <input type="number" class="input1" name="age" id="age" min="5" max="90"
                            value="<?= htmlspecialchars($age); ?>" required readonly>
                    </div>
                    <div class="patient-profile1">
                        <label class="user-label" for="occupation">OCCUPATION</label>
                        <input name="occupation" id="occupation" class="input1" type="text"
                            value="<?= htmlspecialchars($occupation); ?>" required>
                    </div>
                </div>

                <div class="patient-profile-container">
                    <div class="patient-profile1">
                        <label class="label" for="present_address">PRESENT ADDRESS</label>
                        <input name="present_address" id="present_address" class="inputadd" type="text"
                            value="<?= htmlspecialchars($present_address); ?>" required>
                    </div>

                    <div class="patient-profile1">
                        <label class="label" for="phone_number">PHONE NUMBER</label>
                        <input type="tel" id="phone_number" name="phone_number" class="input1"
                            placeholder="+63 900 000 0000" value="<?= htmlspecialchars($phone_number); ?>" required>
                    </div>
                    <div class="patient-profile1">
                        <label class="label" for="email">EMAIL ADDRESS</label>
                        <input name="email" id="email" class="input1" type="email"
                            value="<?= htmlspecialchars($email); ?>" required>
                    </div>
                </div>

                <h1>DENTAL AND MEDICAL HISTORY</h1>
                <hr class="thin-line">
            
                    <p>PLEASE CIRCLE APPROPRIATE ANSWER:</p>

                    <div class="medical-question">
                        <label>1. ARE YOU HAVING PAIN OR DISCOMFORT AT THIS TIME?</label>
                        <div>
                            <input type="radio" name="q1" value="Yes" <?= htmlspecialchars($q1) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q1" value="No" <?= htmlspecialchars($q1) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>2. DO YOU FEEL NERVOUS ABOUT HAVING DENTAL TREATMENT?</label>
                        <div>
                            <input type="radio" name="q2" value="Yes" <?= htmlspecialchars($q2) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q2" value="No" <?= htmlspecialchars($q2) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>3. HAVE YOU EVER HAD A BAD EXPERIENCE IN THE DENTAL OFFICE?</label>
                        <div>
                            <input type="radio" name="q3" value="Yes" <?= htmlspecialchars($q3) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q3" value="No" <?= htmlspecialchars($q3) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>4. HAVE YOU BEEN UNDER THE CARE OF A MEDICAL DOCTOR DURING THE PAST TWO YEARS?</label>
                        <div>
                            <input type="radio" name="q4" value="Yes" <?= htmlspecialchars($q4) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q4" value="No" <?= htmlspecialchars($q4) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>5. HAVE YOU BEEN TAKING MEDICINE OR DRUGS DURING THE PAST TWO YEARS?</label>
                        <div>
                            <input type="radio" name="q5" value="Yes" <?= htmlspecialchars($q5) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q5" value="No" <?= htmlspecialchars($q5) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>IF YES, PLEASE INDICATE THE FOLLOWING MEDICATION/S:</label>
                        <textarea name="medications" rows="3"><?= htmlspecialchars($medications); ?></textarea>

                        <label>6. ARE YOU ALLERGIC TO OR MAKE SICK BY PENICILLIN, ASPIRIN, OR ANY FOOD, DRUGS, OR
                            MEDICATION?</label>
                        <div>
                            <input type="radio" name="q6" value="Yes" <?= htmlspecialchars($q6) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q6" value="No" <?= htmlspecialchars($q6) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>7. HAVE YOU EVER HAD EXCESSIVE BLEEDING REQUIRING SPECIAL TREATMENT?</label>
                        <div>
                            <input type="radio" name="q7" value="Yes" <?= htmlspecialchars($q7) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q7" value="No" <?= htmlspecialchars($q7) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>8. CIRCLE ANY OF THE FOLLOWING IN WHICH YOU HAVE HAD OR HAVE AT PRESENT:</label>
                        <div class="checkbox-group">
                            <?php
                            $conditions = [
                                "Heart Failure",
                                "Emphysema",
                                "AIDS",
                                "Angina Pectoris",
                                "Heart Disease",
                                "Hepa-B",
                                "High Blood Pressure",
                                "Asthma",
                                "Tuberculosis",
                                "Congenital Heart",
                                "Heart Pacemaker",
                                "Drug Addiction",
                                "Epilepsy",
                                "Heart Surgery",
                                "Chemotherapy",
                                "Diabetes",
                                "Venereal Disease",
                                "Hemophilia",
                                "Anemia",
                                "Rheumatism",
                                "Cold Sores",
                                "Stroke",
                                "Artificial Joint",
                                "Arthritis",
                                "Sinus Trouble",
                                "Fainting",
                                "Blood Transfusion",
                                "Artificial Heart Valve"
                            ];
                            foreach ($conditions as $condition) {
                                $checked = in_array($condition, $q8) ? 'checked' : '';
                                echo "<label><input type='checkbox' name='q8[]' value='$condition' $checked> $condition</label>";
                            }
                            ?>
                        </div>

                        <label>9. DO YOU HAVE ANY DISEASE, CONDITION, OR PROBLEM NOT LISTED?</label>
                        <textarea name="q9" rows="3"
                            placeholder="PLEASE SPECIFY IF ANY..."><?= htmlspecialchars($q9); ?></textarea>

                        <label>10. WHEN YOU WALK UP STAIRS OR TAKE A WALK, DO YOU EVER STOP BECAUSE OF PAIN IN YOUR
                            CHEST OR SHORTNESS OF BREATH OR BEING TIRED?</label>
                        <div>
                            <input type="radio" name="q10" value="Yes" <?= htmlspecialchars($q10) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q10" value="No" <?= htmlspecialchars($q10) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>11. DO YOUR ANKLES SWELL DURING THE DAY?</label>
                        <div>
                            <input type="radio" name="q11" value="Yes" <?= htmlspecialchars($q11) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q11" value="No" <?= htmlspecialchars($q11) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>12. DO YOU EVER WAKE UP FROM SLEEP BECAUSE OF SHORTNESS OF BREATH?</label>
                        <div>
                            <input type="radio" name="q12" value="Yes" <?= htmlspecialchars($q12) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q12" value="No" <?= htmlspecialchars($q12) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <p>13. WOMEN:</p>
                        <label>ARE YOU PREGNANT NOW?</label>
                        <div>
                            <input type="radio" name="q13a" value="Yes" <?= htmlspecialchars($q13a) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q13a" value="No" <?= htmlspecialchars($q13a) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>ARE PRACTICING BIRTH CONTROL?</label>
                        <div>
                            <input type="radio" name="q13b" value="Yes" <?= htmlspecialchars($q13b) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q13b" value="No" <?= htmlspecialchars($q13b) === 'No' ? 'checked' : ''; ?>> NO
                        </div>

                        <label>DO YOU ANTICIPATE BECOMING PREGNANT?</label>
                        <div>
                            <input type="radio" name="q13c" value="Yes" <?= htmlspecialchars($q13c) === 'Yes' ? 'checked' : ''; ?>> YES
                            <input type="radio" name="q13c" value="No" <?= htmlspecialchars($q13c) === 'No' ? 'checked' : ''; ?>> NO
                        </div>
                    </div>


                    <button type="submit" class="save-btn">SAVE</button>
            </form>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/updateprofile.js"></script>
</body>

</html>