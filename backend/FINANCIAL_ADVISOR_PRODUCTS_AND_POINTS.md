# Financial Advisor - Products & Points Integration
# TODO:Not yet finished!!
## 🎯 **Enhanced Features**

### **1. Product Inquiries**
Users can now ask about products at merchants without making purchases.

#### **Example Conversations:**

**Product Inquiry:**
```
User: "What products does SM Supermarket have?"
AI: "Products at SM Supermarket:
• Jasmine Rice 5kg - ₱250.00 (Earns 2 points per peso)
• Fresh Milk 1L - ₱85.00 (Earns 1 point per peso)
• Whole Wheat Bread - ₱45.00 (No points program)
• Organic Eggs 12pcs - ₱120.00 (Earns 5 points)

Summary: 4 products, Total Value: ₱500.00, 3 products with points programs"
```

**Purchase Calculation:**
```
User: "I want to buy 2 bags of rice and 3 bottles of milk from SM Supermarket. How much will it cost and how many points can I earn?"
AI: "Purchase Summary for SM Supermarket:
• Jasmine Rice 5kg x2 = ₱500.00 (Earns 1000 points)
• Fresh Milk 1L x3 = ₱255.00 (Earns 255 points)

Total Cost: ₱755.00
Total Points to Earn: 1255"
```

### **2. Enhanced Purchase Recording**

#### **System Merchant Purchase:**
```
User: "I bought groceries for 500 pesos at SM Supermarket today"
AI: "Purchase Confirmation:
• Item: Groceries
• Amount: ₱500
• Merchant: SM Supermarket (System Merchant)
• Date: 2025-07-27
• Points: You can earn points at this merchant!

Purchase details confirmed and ready to record."
```

#### **External Merchant Purchase:**
```
User: "I bought a coffee for 150 pesos at Starbucks yesterday"
AI: "Purchase Confirmation:
• Item: Coffee
• Amount: ₱150
• Merchant: Starbucks (External Merchant)
• Date: 2025-07-26

Purchase details confirmed and ready to record."
```

### **3. Points Integration**

#### **Check Merchant Points:**
```
User: "How many points do I have at SM Supermarket?"
AI: "Yes! You can earn points at SM Supermarket! Currently you have 140 points at this merchant!"
```

#### **All Points Summary:**
```
User: "How many points do I have?"
AI: "Your Points Summary:
• SM Supermarket: 140 points
• Puregold: 20 points
• Robinsons: 85 points

Total Points: 245"
```

## 🔧 **Technical Implementation**

### **New Tools Added:**

#### **1. `get_merchant_products`**
- **Purpose**: Get products available at a merchant with prices and points earning potential
- **Parameters**: `merchant_name` (string)
- **Returns**: Product list with prices and points information

#### **2. `calculate_purchase_points`**
- **Purpose**: Calculate total cost and points for specific items
- **Parameters**: 
  - `merchant_name` (string)
  - `items` (array of objects with `product_name` and `quantity`)
- **Returns**: Detailed purchase summary with costs and points

#### **3. `confirm_purchase_details`**
- **Purpose**: Confirm purchase details and identify merchant type
- **Parameters**:
  - `title` (string)
  - `amount` (number)
  - `merchant_name` (string, optional)
  - `date` (string, YYYY-MM-DD)
  - `description` (string, optional)
  - `is_system_merchant` (boolean)
- **Returns**: Confirmation message with merchant identification

### **Enhanced Purchase Flow:**

1. **User mentions purchase** → AI identifies merchant type
2. **System merchant** → Auto-identify and check points potential
3. **External merchant** → Ask for merchant name
4. **Confirm details** → Show purchase summary
5. **Record purchase** → Add to financial records

### **Points Calculation:**

#### **Product-Level Points:**
- **Fixed**: Earn X points per product
- **Dynamic**: Earn X points per peso spent
- **Combo**: Points based on amount + quantity

#### **Merchant-Level Points:**
- **Threshold**: Bonus points for spending over X amount
- **First Purchase**: Bonus for first-time customers
- **Limited Time**: Time-limited promotions

## 📊 **Database Schema**

### **UserMerchantPoints Table:**
```sql
user_merchant_points:
- user_id (FK)
- merchant_id (FK)
- points (current balance)
- total_earned (lifetime)
- total_spent (lifetime)
- last_earned_at
- last_spent_at
```

### **Points Isolation:**
- **SM Points**: Can only be used at SM stores
- **Puregold Points**: Can only be used at Puregold stores
- **No Cross-Merchant Spending**: Points are locked to their merchant

## 🚀 **API Usage**

### **Product Inquiry:**
```bash
POST /api/financial-advisor/chat
{
    "message": "What products does SM Supermarket have?"
}
```

### **Purchase Calculation:**
```bash
POST /api/financial-advisor/chat
{
    "message": "I want to buy 2 bags of rice and 3 bottles of milk from SM Supermarket. How much will it cost and how many points can I earn?"
}
```

### **Purchase Recording:**
```bash
POST /api/financial-advisor/chat
{
    "message": "I bought groceries for 500 pesos at SM Supermarket today"
}
```

### **Points Check:**
```bash
POST /api/financial-advisor/chat
{
    "message": "How many points do I have at SM Supermarket?"
}
```

## 🧪 **Testing**

### **Test Command:**
```bash
php artisan financial-advisor:test-points
```

### **Test Scenarios:**
1. **Product Inquiry**: Check available products at merchants
2. **Purchase Calculation**: Calculate costs and points for specific items
3. **Points Check**: Verify points balance at specific merchants
4. **System Merchant Purchase**: Record purchase at system merchant
5. **External Merchant Purchase**: Record purchase at external merchant

## 💡 **Key Features**

### **Smart Merchant Identification:**
- **System Merchants**: Automatically identified and linked
- **External Merchants**: Prompted for name and recorded separately
- **Points Integration**: Only system merchants can earn points

### **Product Intelligence:**
- **Price Information**: Real-time product prices
- **Points Potential**: Shows points earning for each product
- **Purchase Planning**: Calculate total cost and points before buying

### **Enhanced User Experience:**
- **Natural Language**: Users can ask in plain English
- **Confirmation Flow**: Purchase details confirmed before recording
- **Points Awareness**: Always informed about points earning potential

### **Financial Tracking:**
- **Merchant-Specific**: Points tracked per merchant
- **Purchase History**: Complete record of all purchases
- **Points Analytics**: Track earning and spending patterns

## 🔄 **Workflow Examples**

### **Scenario 1: Planning a Purchase**
1. User: "What products does SM have?"
2. AI: Shows product list with prices and points
3. User: "I want to buy rice and milk"
4. AI: Calculates total cost and points
5. User: "Great, I'll buy that"
6. AI: Confirms purchase details and records

### **Scenario 2: Recording Past Purchase**
1. User: "I bought coffee at Starbucks yesterday"
2. AI: "What was the amount?"
3. User: "150 pesos"
4. AI: Confirms external merchant purchase
5. AI: Records purchase in financial history

### **Scenario 3: Points Check**
1. User: "How many points do I have?"
2. AI: Shows points summary across all merchants
3. User: "Can I earn points at Puregold?"
4. AI: Checks merchant and shows points status

This enhanced system provides a complete financial management experience with integrated loyalty points, product information, and intelligent purchase tracking. 
