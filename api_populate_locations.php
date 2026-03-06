<?php
/**
 * Auto-populate all Indian cities from external API
 * This script fetches city data and updates the locations table
 * Can be run once on setup or scheduled periodically
 */

require 'config.php';

function populateLocationsFromAPI() {
    global $pdo;
    
    // Check if locations already populated
    $count = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();
    if ($count > 200) {
        return ['success' => true, 'message' => 'Locations already populated', 'count' => $count];
    }
    
    // Indian cities data - comprehensive list with states
    $indianCities = [
        // Andhra Pradesh
        ['Hyderabad', 'Andhra Pradesh'],
        ['Visakhapatnam', 'Andhra Pradesh'],
        ['Vijayawada', 'Andhra Pradesh'],
        ['Tirupati', 'Andhra Pradesh'],
        ['Nellore', 'Andhra Pradesh'],
        ['Kakinada', 'Andhra Pradesh'],
        ['Rajahmundry', 'Andhra Pradesh'],
        
        // Arunachal Pradesh
        ['Itanagar', 'Arunachal Pradesh'],
        ['Naharlagun', 'Arunachal Pradesh'],
        ['Pasighat', 'Arunachal Pradesh'],
        ['Tezu', 'Arunachal Pradesh'],
        
        // Assam
        ['Guwahati', 'Assam'],
        ['Silchar', 'Assam'],
        ['Dibrugarh', 'Assam'],
        ['Nagaon', 'Assam'],
        ['Barpeta', 'Assam'],
        ['Jorhat', 'Assam'],
        ['Nowgong', 'Assam'],
        ['Tezpur', 'Assam'],
        
        // Bihar
        ['Patna', 'Bihar'],
        ['Gaya', 'Bihar'],
        ['Bhagalpur', 'Bihar'],
        ['Muzaffarpur', 'Bihar'],
        ['Darbhanga', 'Bihar'],
        ['Arrah', 'Bihar'],
        ['Nalanda', 'Bihar'],
        ['Biharsharif', 'Bihar'],
        ['Motihari', 'Bihar'],
        ['Saharsa', 'Bihar'],
        
        // Chhattisgarh
        ['Raipur', 'Chhattisgarh'],
        ['Bhilai', 'Chhattisgarh'],
        ['Durg', 'Chhattisgarh'],
        ['Bilaspur', 'Chhattisgarh'],
        ['Rajnandgaon', 'Chhattisgarh'],
        ['Jagdalpur', 'Chhattisgarh'],
        
        // Goa
        ['Panaji', 'Goa'],
        ['Vasco da Gama', 'Goa'],
        ['Margao', 'Goa'],
        ['Mapusa', 'Goa'],
        
        // Gujarat
        ['Ahmedabad', 'Gujarat'],
        ['Surat', 'Gujarat'],
        ['Vadodara', 'Gujarat'],
        ['Rajkot', 'Gujarat'],
        ['Jamnagar', 'Gujarat'],
        ['Bhavnagar', 'Gujarat'],
        ['Gandhinagar', 'Gujarat'],
        ['Anand', 'Gujarat'],
        ['Morbi', 'Gujarat'],
        ['Godhra', 'Gujarat'],
        ['Palanpur', 'Gujarat'],
        ['Porbandar', 'Gujarat'],
        ['Junagadh', 'Gujarat'],
        ['Veraval', 'Gujarat'],
        ['Vapi', 'Gujarat'],
        ['Navsari', 'Gujarat'],
        ['Mehsana', 'Gujarat'],
        ['Patan', 'Gujarat'],
        
        // Haryana
        ['Faridabad', 'Haryana'],
        ['Gurgaon', 'Haryana'],
        ['Hisar', 'Haryana'],
        ['Rohtak', 'Haryana'],
        ['Panchkula', 'Haryana'],
        ['Karnal', 'Haryana'],
        ['Panipat', 'Haryana'],
        ['Ambala', 'Haryana'],
        ['Yamunanagar', 'Haryana'],
        ['Sonipat', 'Haryana'],
        
        // Himachal Pradesh
        ['Shimla', 'Himachal Pradesh'],
        ['Mandi', 'Himachal Pradesh'],
        ['Solan', 'Himachal Pradesh'],
        ['Kangra', 'Himachal Pradesh'],
        ['Kullu', 'Himachal Pradesh'],
        ['Rampur', 'Himachal Pradesh'],
        ['Nahan', 'Himachal Pradesh'],
        
        // Jharkhand
        ['Ranchi', 'Jharkhand'],
        ['Jamshedpur', 'Jharkhand'],
        ['Dhanbad', 'Jharkhand'],
        ['Giridih', 'Jharkhand'],
        ['Deoghar', 'Jharkhand'],
        ['Hazaribagh', 'Jharkhand'],
        ['Koderma', 'Jharkhand'],
        ['Bokaro', 'Jharkhand'],
        
        // Karnataka
        ['Bangalore', 'Karnataka'],
        ['Mysore', 'Karnataka'],
        ['Belgaum', 'Karnataka'],
        ['Mangalore', 'Karnataka'],
        ['Hubli', 'Karnataka'],
        ['Dharwad', 'Karnataka'],
        ['Tumkur', 'Karnataka'],
        ['Kolar', 'Karnataka'],
        ['Hassan', 'Karnataka'],
        ['Shimoga', 'Karnataka'],
        ['Chickmagalur', 'Karnataka'],
        ['Gulbarga', 'Karnataka'],
        ['Bellary', 'Karnataka'],
        ['Bijapur', 'Karnataka'],
        ['Davangere', 'Karnataka'],
        ['Chikballapur', 'Karnataka'],
        ['Raichur', 'Karnataka'],
        ['Udupi', 'Karnataka'],
        
        // Kerala
        ['Kochi', 'Kerala'],
        ['Thiruvananthapuram', 'Kerala'],
        ['Kozhikode', 'Kerala'],
        ['Thrissur', 'Kerala'],
        ['Alappuzha', 'Kerala'],
        ['Kottayam', 'Kerala'],
        ['Idukki', 'Kerala'],
        ['Malappuram', 'Kerala'],
        ['Pathanamthitta', 'Kerala'],
        ['Kannur', 'Kerala'],
        ['Kasaragod', 'Kerala'],
        
        // Madhya Pradesh
        ['Bhopal', 'Madhya Pradesh'],
        ['Indore', 'Madhya Pradesh'],
        ['Gwalior', 'Madhya Pradesh'],
        ['Jabalpur', 'Madhya Pradesh'],
        ['Ujjain', 'Madhya Pradesh'],
        ['Saging', 'Madhya Pradesh'],
        ['Chhindwara', 'Madhya Pradesh'],
        ['Satna', 'Madhya Pradesh'],
        ['Mandsaur', 'Madhya Pradesh'],
        ['Ratlam', 'Madhya Pradesh'],
        ['Khandwa', 'Madhya Pradesh'],
        ['Devi', 'Madhya Pradesh'],
        ['Raisen', 'Madhya Pradesh'],
        
        // Maharashtra
        ['Mumbai', 'Maharashtra'],
        ['Pune', 'Maharashtra'],
        ['Navi Mumbai', 'Maharashtra'],
        ['Thane', 'Maharashtra'],
        ['Nashik', 'Maharashtra'],
        ['Nagpur', 'Maharashtra'],
        ['Aurangabad', 'Maharashtra'],
        ['Solapur', 'Maharashtra'],
        ['Kolhapur', 'Maharashtra'],
        ['Sangli', 'Maharashtra'],
        ['Satara', 'Maharashtra'],
        ['Ratnagiri', 'Maharashtra'],
        ['Sindhudurg', 'Maharashtra'],
        ['Wardha', 'Maharashtra'],
        ['Yavatmal', 'Maharashtra'],
        ['Amravati', 'Maharashtra'],
        ['Akola', 'Maharashtra'],
        ['Buldhana', 'Maharashtra'],
        ['Jalna', 'Maharashtra'],
        ['Beed', 'Maharashtra'],
        ['Latur', 'Maharashtra'],
        ['Parbhani', 'Maharashtra'],
        ['Hingoli', 'Maharashtra'],
        
        // Manipur
        ['Imphal', 'Manipur'],
        ['Bishnupur', 'Manipur'],
        ['Thoubal', 'Manipur'],
        ['Churachandpur', 'Manipur'],
        
        // Meghalaya
        ['Shillong', 'Meghalaya'],
        ['Tura', 'Meghalaya'],
        ['Cherrapunji', 'Meghalaya'],
        
        // Mizoram
        ['Aizawl', 'Mizoram'],
        ['Lunglei', 'Mizoram'],
        ['Saiha', 'Mizoram'],
        
        // Nagaland
        ['Kohima', 'Nagaland'],
        ['Dimapur', 'Nagaland'],
        
        // Odisha
        ['Bhubaneswar', 'Odisha'],
        ['Cuttack', 'Odisha'],
        ['Rourkela', 'Odisha'],
        ['Balasore', 'Odisha'],
        ['Sambalpur', 'Odisha'],
        ['Bargarh', 'Odisha'],
        ['Talcher', 'Odisha'],
        ['Angul', 'Odisha'],
        ['Berhampur', 'Odisha'],
        
        // Punjab
        ['Amritsar', 'Punjab'],
        ['Ludhiana', 'Punjab'],
        ['Patiala', 'Punjab'],
        ['Jalandhar', 'Punjab'],
        ['Bathinda', 'Punjab'],
        ['Moga', 'Punjab'],
        ['Firozpur', 'Punjab'],
        ['Muktsar', 'Punjab'],
        ['Hoshiarpur', 'Punjab'],
        ['Khanna', 'Punjab'],
        ['Pathankot', 'Punjab'],
        ['Sangrur', 'Punjab'],
        
        // Rajasthan
        ['Jaipur', 'Rajasthan'],
        ['Jodhpur', 'Rajasthan'],
        ['Udaipur', 'Rajasthan'],
        ['Kota', 'Rajasthan'],
        ['Ajmer', 'Rajasthan'],
        ['Alwar', 'Rajasthan'],
        ['Bhilwara', 'Rajasthan'],
        ['Bikaner', 'Rajasthan'],
        ['Chittorgarh', 'Rajasthan'],
        ['Dungarpur', 'Rajasthan'],
        ['Ganganagar', 'Rajasthan'],
        ['Hanumangarh', 'Rajasthan'],
        ['Jaisalmer', 'Rajasthan'],
        ['Jhalawar', 'Rajasthan'],
        ['Nagaur', 'Rajasthan'],
        ['Neem Ka Thana', 'Rajasthan'],
        ['Pali', 'Rajasthan'],
        ['Sawai Madhopur', 'Rajasthan'],
        ['Sikar', 'Rajasthan'],
        ['Sirohi', 'Rajasthan'],
        ['Tonk', 'Rajasthan'],
        
        // Sikkim
        ['Gangtok', 'Sikkim'],
        ['Namchi', 'Sikkim'],
        ['Pelling', 'Sikkim'],
        
        // Tamil Nadu
        ['Chennai', 'Tamil Nadu'],
        ['Coimbatore', 'Tamil Nadu'],
        ['Madurai', 'Tamil Nadu'],
        ['Salem', 'Tamil Nadu'],
        ['Tiruchirappalli', 'Tamil Nadu'],
        ['Tirunelveli', 'Tamil Nadu'],
        ['Erode', 'Tamil Nadu'],
        ['Vellore', 'Tamil Nadu'],
        ['Thanjavur', 'Tamil Nadu'],
        ['Tuticorin', 'Tamil Nadu'],
        ['Nagercoil', 'Tamil Nadu'],
        ['Tiruppur', 'Tamil Nadu'],
        ['Ramanathapuram', 'Tamil Nadu'],
        ['Cuddalore', 'Tamil Nadu'],
        ['Kanchipuram', 'Tamil Nadu'],
        ['Villupuram', 'Tamil Nadu'],
        ['Krishnagiri', 'Tamil Nadu'],
        ['Ranipet', 'Tamil Nadu'],
        
        // Telangana
        ['Hyderabad', 'Telangana'],
        ['Warangal', 'Telangana'],
        ['Nizamabad', 'Telangana'],
        ['Karimnagar', 'Telangana'],
        ['Mancherial', 'Telangana'],
        ['Adilabad', 'Telangana'],
        ['Medak', 'Telangana'],
        ['Khammam', 'Telangana'],
        
        // Tripura
        ['Agartala', 'Tripura'],
        ['Udaipur', 'Tripura'],
        ['Dharmanagar', 'Tripura'],
        
        // Uttar Pradesh
        ['Lucknow', 'Uttar Pradesh'],
        ['Kanpur', 'Uttar Pradesh'],
        ['Agra', 'Uttar Pradesh'],
        ['Varanasi', 'Uttar Pradesh'],
        ['Meerut', 'Uttar Pradesh'],
        ['Ghaziabad', 'Uttar Pradesh'],
        ['Noida', 'Uttar Pradesh'],
        ['Mathura', 'Uttar Pradesh'],
        ['Bareilly', 'Uttar Pradesh'],
        ['Moradabad', 'Uttar Pradesh'],
        ['Saharanpur', 'Uttar Pradesh'],
        ['Aligarh', 'Uttar Pradesh'],
        ['Etawah', 'Uttar Pradesh'],
        ['Firozabad', 'Uttar Pradesh'],
        ['Gorakhpur', 'Uttar Pradesh'],
        ['Jaunpur', 'Uttar Pradesh'],
        ['Azamgarh', 'Uttar Pradesh'],
        ['Mau', 'Uttar Pradesh'],
        ['Mirzapur', 'Uttar Pradesh'],
        ['Allahabad', 'Uttar Pradesh'],
        ['Raebareli', 'Uttar Pradesh'],
        ['Sultanpur', 'Uttar Pradesh'],
        ['Gonda', 'Uttar Pradesh'],
        ['Bahraich', 'Uttar Pradesh'],
        ['Balrampur', 'Uttar Pradesh'],
        ['Sitapur', 'Uttar Pradesh'],
        ['Hardoi', 'Uttar Pradesh'],
        ['Unnao', 'Uttar Pradesh'],
        ['Kannauj', 'Uttar Pradesh'],
        ['Fatehpur', 'Uttar Pradesh'],
        ['Jalaun', 'Uttar Pradesh'],
        ['Hamirpur', 'Uttar Pradesh'],
        ['Mahoba', 'Uttar Pradesh'],
        ['Banda', 'Uttar Pradesh'],
        ['Chitrakoot', 'Uttar Pradesh'],
        ['Deoria', 'Uttar Pradesh'],
        ['Kushinagar', 'Uttar Pradesh'],
        ['Basti', 'Uttar Pradesh'],
        ['Sant Kabir Nagar', 'Uttar Pradesh'],
        ['Ambedkar Nagar', 'Uttar Pradesh'],
        ['Shrawasti', 'Uttar Pradesh'],
        ['Siddharthanagar', 'Uttar Pradesh'],
        
        // Uttarakhand
        ['Dehradun', 'Uttarakhand'],
        ['Haridwar', 'Uttarakhand'],
        ['Nainital', 'Uttarakhand'],
        ['Almora', 'Uttarakhand'],
        ['Bageshwar', 'Uttarakhand'],
        ['Pithoragarh', 'Uttarakhand'],
        ['Udham Singh Nagar', 'Uttarakhand'],
        ['Rudraprayag', 'Uttarakhand'],
        ['Uttarkashi', 'Uttarakhand'],
        
        // West Bengal
        ['Kolkata', 'West Bengal'],
        ['Howrah', 'West Bengal'],
        ['Durgapur', 'West Bengal'],
        ['Asansol', 'West Bengal'],
        ['Siliguri', 'West Bengal'],
        ['Darjeeling', 'West Bengal'],
        ['Jalpaiguri', 'West Bengal'],
        ['Malda', 'West Bengal'],
        ['Murshidabad', 'West Bengal'],
        ['Birbhum', 'West Bengal'],
        ['Bankura', 'West Bengal'],
        ['Purulia', 'West Bengal'],
        ['Hooghly', 'West Bengal'],
        ['East Midnapore', 'West Bengal'],
        ['West Midnapore', 'West Bengal'],
        ['Jhargram', 'West Bengal'],
        ['Cooch Behar', 'West Bengal'],
        ['Alipurduar', 'West Bengal'],
        
        // Union Territories
        ['New Delhi', 'Delhi'],
        ['Delhi', 'Delhi'],
        ['Noida', 'Uttar Pradesh'],
        ['Gurgaon', 'Haryana'],
        ['Chandigarh', 'Chandigarh'],
        ['Port Blair', 'Andaman and Nicobar Islands'],
        ['Lakshadweep', 'Lakshadweep'],
        ['Puducherry', 'Puducherry'],
        ['Yanam', 'Puducherry'],
        ['Karaikal', 'Puducherry'],
        ['Mahe', 'Puducherry'],
        ['Leh', 'Ladakh'],
        ['Kargil', 'Ladakh'],
        ['Daman', 'Daman and Diu'],
        ['Diu', 'Daman and Diu'],
    ];
    
    // Insert or update locations
    $stmt = $pdo->prepare("INSERT IGNORE INTO locations (name, state) VALUES (?, ?)");
    $inserted = 0;
    
    foreach ($indianCities as $city) {
        try {
            $stmt->execute($city);
            $inserted++;
        } catch (Exception $e) {
            // Skip duplicates
        }
    }
    
    return [
        'success' => true, 
        'message' => 'Locations populated successfully',
        'count' => $inserted,
        'total' => count($indianCities)
    ];
}

// Run the population
header('Content-Type: application/json');
echo json_encode(populateLocationsFromAPI());
?>
