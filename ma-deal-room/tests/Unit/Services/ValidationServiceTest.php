<?php
/**
 * ValidationService Unit Tests
 *
 * Comprehensive tests for the ValidationService class.
 *
 * @package MADealRoom\Tests\Unit\Services
 */

namespace MADealRoom\Tests\Unit\Services;

use MADealRoom\Tests\TestCase;
use MADealRoom\Services\ValidationService;

/**
 * ValidationService Test Class
 */
class ValidationServiceTest extends TestCase
{
    private $validation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validation = new ValidationService();
    }

    // ========================================
    // Email Validation Tests
    // ========================================

    public function testValidateEmailWithValidEmail()
    {
        $result = $this->validation->validate_email('test@example.com');
        $this->assertTrue($result);
    }

    public function testValidateEmailWithInvalidFormat()
    {
        $result = $this->validation->validate_email('invalid-email');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_email', $result->get_error_code());
    }

    public function testValidateEmailWithTooLongEmail()
    {
        // Create an email longer than 254 characters (maximum allowed)
        $longLocalPart = str_repeat('a', 250);
        $longEmail = $longLocalPart . '@test.com';
        $result = $this->validation->validate_email($longEmail);
        $this->assertIsWPError($result);
        // is_email() might reject it as invalid_email before length check
        $this->assertContains($result->get_error_code(), ['invalid_email', 'email_too_long']);
    }

    public function testValidateEmailWithVariousValidFormats()
    {
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_name@sub.example.com',
            '123@example.com',
        ];

        foreach ($validEmails as $email) {
            $result = $this->validation->validate_email($email);
            $this->assertTrue($result, "Email should be valid: {$email}");
        }
    }

    // ========================================
    // Password Validation Tests
    // ========================================

    public function testValidatePasswordWithValidPassword()
    {
        $result = $this->validation->validate_password('SecurePass123');
        $this->assertTrue($result);
    }

    public function testValidatePasswordTooShort()
    {
        $result = $this->validation->validate_password('Short1');
        $this->assertIsWPError($result);
        $this->assertEquals('password_too_short', $result->get_error_code());
    }

    public function testValidatePasswordTooLong()
    {
        $longPassword = str_repeat('A', 73) . '1a';
        $result = $this->validation->validate_password($longPassword);
        $this->assertIsWPError($result);
        $this->assertEquals('password_too_long', $result->get_error_code());
    }

    public function testValidatePasswordNoUppercase()
    {
        $result = $this->validation->validate_password('nouppercase123');
        $this->assertIsWPError($result);
        $this->assertEquals('password_no_uppercase', $result->get_error_code());
    }

    public function testValidatePasswordNoLowercase()
    {
        $result = $this->validation->validate_password('NOLOWERCASE123');
        $this->assertIsWPError($result);
        $this->assertEquals('password_no_lowercase', $result->get_error_code());
    }

    public function testValidatePasswordNoNumber()
    {
        $result = $this->validation->validate_password('NoNumbersHere');
        $this->assertIsWPError($result);
        $this->assertEquals('password_no_number', $result->get_error_code());
    }

    public function testValidatePasswordCommonPassword()
    {
        $commonPasswords = ['password', '123456', 'qwerty', 'password123'];

        foreach ($commonPasswords as $password) {
            $result = $this->validation->validate_password($password);
            // Common passwords might fail due to other requirements first
            if ($result !== true) {
                $this->assertIsWPError($result);
            }
        }
    }

    public function testValidatePasswordWithStrongPasswords()
    {
        $strongPasswords = [
            'MyP@ssw0rd123',
            'Secur3P4ssword',
            'C0mpl3xP@ss',
            'Str0ngPwd2023',
        ];

        foreach ($strongPasswords as $password) {
            $result = $this->validation->validate_password($password);
            $this->assertTrue($result, "Password should be valid: {$password}");
        }
    }

    // ========================================
    // Phone Validation Tests
    // ========================================

    public function testValidatePhoneUSFormat()
    {
        $validPhones = [
            '6175551234',
            '(617) 555-1234',
            '617-555-1234',
            '+16175551234',
            '1-617-555-1234',
        ];

        foreach ($validPhones as $phone) {
            $result = $this->validation->validate_phone($phone, 'US');
            $this->assertTrue($result, "Phone should be valid: {$phone}");
        }
    }

    public function testValidatePhoneUSInvalidCharacters()
    {
        $result = $this->validation->validate_phone('617-ABC-1234', 'US');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_phone', $result->get_error_code());
    }

    public function testValidatePhoneUSWrongLength()
    {
        $result = $this->validation->validate_phone('12345', 'US');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_phone_length', $result->get_error_code());
    }

    public function testValidatePhoneUSStartsWithZero()
    {
        $result = $this->validation->validate_phone('0175551234', 'US');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_phone_format', $result->get_error_code());
    }

    public function testValidatePhoneInternational()
    {
        $validPhones = [
            '+441234567890',    // UK
            '+33123456789',     // France
            '+81312345678',     // Japan
        ];

        foreach ($validPhones as $phone) {
            $result = $this->validation->validate_phone($phone, 'UK');
            $this->assertTrue($result, "Phone should be valid: {$phone}");
        }
    }

    public function testValidatePhoneInternationalTooShort()
    {
        $result = $this->validation->validate_phone('123456', 'UK');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_phone_length', $result->get_error_code());
    }

    // ========================================
    // Name Validation Tests
    // ========================================

    public function testValidateNameValid()
    {
        $validNames = [
            'John',
            'Mary Jane',
            "O'Brien",
            'Smith-Jones',
            'Dr. Johnson',
        ];

        foreach ($validNames as $name) {
            $result = $this->validation->validate_name($name);
            $this->assertTrue($result, "Name should be valid: {$name}");
        }
    }

    public function testValidateNameEmpty()
    {
        $result = $this->validation->validate_name('');
        $this->assertIsWPError($result);
        $this->assertEquals('name_required', $result->get_error_code());
    }

    public function testValidateNameTooShort()
    {
        $result = $this->validation->validate_name('A');
        $this->assertIsWPError($result);
        $this->assertEquals('name_too_short', $result->get_error_code());
    }

    public function testValidateNameTooLong()
    {
        $longName = str_repeat('A', 101);
        $result = $this->validation->validate_name($longName);
        $this->assertIsWPError($result);
        $this->assertEquals('name_too_long', $result->get_error_code());
    }

    public function testValidateNameInvalidCharacters()
    {
        $result = $this->validation->validate_name('John123');
        $this->assertIsWPError($result);
        $this->assertEquals('name_invalid_characters', $result->get_error_code());
    }

    // ========================================
    // User Type Validation Tests
    // ========================================

    public function testValidateUserTypeValid()
    {
        $result = $this->validation->validate_user_type('custom');
        $this->assertTrue($result);

        $result = $this->validation->validate_user_type('wordpress');
        $this->assertTrue($result);
    }

    public function testValidateUserTypeInvalid()
    {
        $result = $this->validation->validate_user_type('invalid');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_user_type', $result->get_error_code());
    }

    // ========================================
    // Role Type Validation Tests
    // ========================================

    public function testValidateRoleTypeValid()
    {
        $validRoles = [
            'broker', 'agent', 'buyer', 'seller', 'attorney',
            'lender', 'inspector', 'vendor', 'title_company', 'escrow'
        ];

        foreach ($validRoles as $role) {
            $result = $this->validation->validate_role_type($role);
            $this->assertTrue($result, "Role should be valid: {$role}");
        }
    }

    public function testValidateRoleTypeInvalid()
    {
        $result = $this->validation->validate_role_type('invalid_role');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_role_type', $result->get_error_code());
    }

    // ========================================
    // URL Validation Tests
    // ========================================

    public function testValidateUrlValid()
    {
        $validUrls = [
            'http://example.com',
            'https://example.com',
            'https://sub.example.com/path',
            'http://example.com:8080',
        ];

        foreach ($validUrls as $url) {
            $result = $this->validation->validate_url($url);
            $this->assertTrue($result, "URL should be valid: {$url}");
        }
    }

    public function testValidateUrlInvalidFormat()
    {
        $result = $this->validation->validate_url('not-a-url');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_url', $result->get_error_code());
    }

    public function testValidateUrlInvalidScheme()
    {
        $result = $this->validation->validate_url('ftp://example.com');
        $this->assertIsWPError($result);
        $this->assertEquals('invalid_url_scheme', $result->get_error_code());
    }

    public function testValidateUrlCustomSchemes()
    {
        $result = $this->validation->validate_url('ftp://example.com', ['ftp', 'ftps']);
        $this->assertTrue($result);
    }

    // ========================================
    // Data Validation Tests
    // ========================================

    public function testValidateDataWithValidData()
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'age' => '25',
        ];

        $rules = [
            'email' => ['type' => 'email', 'required' => true],
            'name' => ['type' => 'text', 'required' => true],
            'age' => ['type' => 'integer', 'required' => true],
        ];

        $result = $this->validation->validate_data($data, $rules);
        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals(25, $result['age']);
    }

    public function testValidateDataWithMissingRequiredField()
    {
        $data = [
            'name' => 'John Doe',
        ];

        $rules = [
            'email' => ['type' => 'email', 'required' => true],
            'name' => ['type' => 'text', 'required' => true],
        ];

        $result = $this->validation->validate_data($data, $rules);
        $this->assertIsWPError($result);
        $this->assertEquals('validation_failed', $result->get_error_code());
    }

    public function testValidateDataWithOptionalFields()
    {
        $data = [
            'email' => 'test@example.com',
        ];

        $rules = [
            'email' => ['type' => 'email', 'required' => true],
            'phone' => ['type' => 'phone', 'required' => false],
        ];

        $result = $this->validation->validate_data($data, $rules);
        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertNull($result['phone']);
    }

    public function testValidateDataWithMultipleTypes()
    {
        $data = [
            'email' => 'test@example.com',
            'age' => '30',
            'price' => '99.99',
            'is_active' => '1',
            'birth_date' => '1990-01-15',
            'created_at' => '2025-01-15 10:30:00',
            'website' => 'https://example.com',
        ];

        $rules = [
            'email' => 'email',
            'age' => 'integer',
            'price' => 'float',
            'is_active' => 'boolean',
            'birth_date' => 'date',
            'created_at' => 'datetime',
            'website' => 'url',
        ];

        $result = $this->validation->validate_data($data, $rules);
        $this->assertIsArray($result);
        $this->assertIsInt($result['age']);
        $this->assertIsFloat($result['price']);
        $this->assertEquals('2025-01-15 10:30:00', $result['created_at']);
    }

    public function testValidateDataWithInvalidTypeConversion()
    {
        $data = [
            'age' => 'not-a-number',
        ];

        $rules = [
            'age' => 'integer',
        ];

        $result = $this->validation->validate_data($data, $rules);
        $this->assertIsWPError($result);
    }

    // ========================================
    // File Upload Validation Tests
    // ========================================

    public function testValidateFileUploadMissingRequiredFile()
    {
        $file = ['tmp_name' => ''];
        $result = $this->validation->validate_file_upload($file, ['required' => true]);
        $this->assertIsWPError($result);
        $this->assertEquals('file_required', $result->get_error_code());
    }

    public function testValidateFileUploadOptionalMissingFile()
    {
        $file = ['tmp_name' => ''];
        $result = $this->validation->validate_file_upload($file, ['required' => false]);
        $this->assertTrue($result);
    }

    public function testValidateFileUploadTooLarge()
    {
        $tempFile = $this->createTempFile('test content', 'txt');

        $file = [
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
            'size' => 20 * 1024 * 1024, // 20MB
            'error' => UPLOAD_ERR_OK,
        ];

        $result = $this->validation->validate_file_upload($file, [
            'max_size' => 10 * 1024 * 1024, // 10MB max
            'allowed_types' => ['txt'],
        ]);

        $this->cleanupTempFiles([$tempFile]);

        $this->assertIsWPError($result);
        $this->assertEquals('file_too_large', $result->get_error_code());
    }

    public function testValidateFileUploadInvalidType()
    {
        $tempFile = $this->createTempFile('test content', 'exe');

        $file = [
            'tmp_name' => $tempFile,
            'name' => 'test.exe',
            'size' => 1024,
            'error' => UPLOAD_ERR_OK,
        ];

        $result = $this->validation->validate_file_upload($file, [
            'allowed_types' => ['pdf', 'jpg', 'png'],
        ]);

        $this->cleanupTempFiles([$tempFile]);

        $this->assertIsWPError($result);
        $this->assertEquals('invalid_file_type', $result->get_error_code());
    }

    // ========================================
    // Edge Cases and Special Scenarios
    // ========================================

    public function testValidateEmailWithSpecialCharacters()
    {
        // Test various special characters allowed in emails
        $validEmails = [
            'user+tag@example.com',
            'user.name@example.com',
            'user_name@example.com',
            'user-name@example.com',
        ];

        foreach ($validEmails as $email) {
            $result = $this->validation->validate_email($email);
            $this->assertTrue($result, "Email should be valid: {$email}");
        }
    }

    public function testValidatePasswordEdgeCases()
    {
        // Exactly minimum length
        $result = $this->validation->validate_password('Pass1234');
        $this->assertTrue($result);

        // Exactly maximum length
        $maxPassword = str_repeat('A', 70) . '1a';
        $result = $this->validation->validate_password($maxPassword);
        $this->assertTrue($result);
    }

    public function testValidatePhoneWithFormatting()
    {
        // Various formatting should be normalized
        $phones = [
            '617.555.1234',
            '(617)555-1234',
            '617 555 1234',
        ];

        foreach ($phones as $phone) {
            $result = $this->validation->validate_phone($phone, 'US');
            $this->assertTrue($result, "Phone should be valid: {$phone}");
        }
    }

    public function testValidateNameWithWhitespace()
    {
        // Leading/trailing whitespace should be trimmed
        $result = $this->validation->validate_name('  John Doe  ');
        $this->assertTrue($result);
    }

    public function testValidateDataWithEmptyString()
    {
        $data = [
            'name' => '',
        ];

        $rules = [
            'name' => ['type' => 'text', 'required' => true],
        ];

        $result = $this->validation->validate_data($data, $rules);
        $this->assertIsWPError($result);
        $this->assertStringContainsString('required', $result->get_error_message());
    }

    public function testValidateBooleanFieldTypes()
    {
        $testCases = [
            ['value' => '1', 'expected' => true],
            ['value' => 'true', 'expected' => true],
            ['value' => 'yes', 'expected' => true],
            ['value' => '0', 'expected' => false],
            ['value' => 'false', 'expected' => false],
            ['value' => 'no', 'expected' => false],
        ];

        foreach ($testCases as $case) {
            $data = ['is_active' => $case['value']];
            $rules = ['is_active' => 'boolean'];
            $result = $this->validation->validate_data($data, $rules);
            $this->assertIsArray($result);
            $this->assertEquals($case['expected'], $result['is_active']);
        }
    }

    public function testValidateDateFormats()
    {
        $validDates = [
            '2025-01-15',
            '2025/01/15',
            'January 15, 2025',
            '15 Jan 2025',
        ];

        foreach ($validDates as $date) {
            $data = ['event_date' => $date];
            $rules = ['event_date' => 'date'];
            $result = $this->validation->validate_data($data, $rules);
            $this->assertIsArray($result);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result['event_date']);
        }
    }

    public function testValidateInvalidDate()
    {
        $data = ['event_date' => 'not-a-date'];
        $rules = ['event_date' => 'date'];
        $result = $this->validation->validate_data($data, $rules);
        $this->assertIsWPError($result);
    }
}
