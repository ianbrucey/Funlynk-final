# E06 Payments & Monetization - Database Schema

## Schema Overview

This document details the database schema for payments and monetization features. This epic adds comprehensive financial infrastructure including payment processing, revenue sharing, subscriptions, and marketplace monetization tools.

## Payment Processing System Schema

### Payment Methods Table
```sql
CREATE TABLE payment_methods (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    stripe_payment_method_id VARCHAR(255) NOT NULL,
    payment_type VARCHAR(20) NOT NULL, -- card, bank_account, digital_wallet
    
    -- Card details (for display only, actual data stored in Stripe)
    card_brand VARCHAR(20), -- visa, mastercard, amex, etc.
    card_last_four VARCHAR(4),
    card_exp_month INTEGER,
    card_exp_year INTEGER,
    
    -- Bank account details (for display only)
    bank_name VARCHAR(100),
    account_last_four VARCHAR(4),
    account_type VARCHAR(20), -- checking, savings
    
    -- Digital wallet details
    wallet_type VARCHAR(20), -- apple_pay, google_pay, paypal
    
    -- Payment method metadata
    is_default BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_status VARCHAR(20) DEFAULT 'pending', -- pending, verified, failed
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_payment_methods_user (user_id),
    INDEX idx_payment_methods_stripe (stripe_payment_method_id),
    INDEX idx_payment_methods_default (user_id, is_default) WHERE is_default = TRUE
);
```

### Transactions Table
```sql
CREATE TABLE transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stripe_payment_intent_id VARCHAR(255) UNIQUE,
    
    -- Transaction parties
    payer_id UUID NOT NULL REFERENCES users(id),
    payee_id UUID REFERENCES users(id), -- NULL for platform fees
    activity_id UUID REFERENCES activities(id),
    
    -- Transaction details
    transaction_type VARCHAR(30) NOT NULL, -- activity_payment, subscription, refund, payout, platform_fee
    amount_cents INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    platform_fee_cents INTEGER NOT NULL DEFAULT 0,
    host_earnings_cents INTEGER NOT NULL DEFAULT 0,
    
    -- Payment processing
    payment_method_id UUID REFERENCES payment_methods(id),
    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, processing, succeeded, failed, canceled, refunded
    payment_processor VARCHAR(20) NOT NULL DEFAULT 'stripe',
    processor_transaction_id VARCHAR(255),
    
    -- Transaction metadata
    description TEXT,
    metadata JSONB DEFAULT '{}',
    failure_reason TEXT,
    refund_reason TEXT,
    
    -- Timestamps
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    processed_at TIMESTAMP WITH TIME ZONE,
    failed_at TIMESTAMP WITH TIME ZONE,
    refunded_at TIMESTAMP WITH TIME ZONE,
    
    INDEX idx_transactions_payer (payer_id, created_at DESC),
    INDEX idx_transactions_payee (payee_id, created_at DESC),
    INDEX idx_transactions_activity (activity_id, payment_status),
    INDEX idx_transactions_status (payment_status, created_at DESC),
    INDEX idx_transactions_stripe (stripe_payment_intent_id),
    INDEX idx_transactions_type (transaction_type, created_at DESC)
);

-- Trigger to update activity payment status
CREATE OR REPLACE FUNCTION update_activity_payment_status()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.transaction_type = 'activity_payment' AND NEW.payment_status = 'succeeded' THEN
        -- Update RSVP payment status
        UPDATE rsvps 
        SET payment_status = 'paid', paid_at = NEW.processed_at
        WHERE activity_id = NEW.activity_id AND user_id = NEW.payer_id;
        
        -- Update activity revenue
        UPDATE activities 
        SET total_revenue_cents = COALESCE(total_revenue_cents, 0) + NEW.amount_cents
        WHERE id = NEW.activity_id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_activity_payment_status
    AFTER UPDATE ON transactions
    FOR EACH ROW EXECUTE FUNCTION update_activity_payment_status();
```

### Refunds Table
```sql
CREATE TABLE refunds (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    original_transaction_id UUID NOT NULL REFERENCES transactions(id),
    stripe_refund_id VARCHAR(255) UNIQUE,
    
    -- Refund details
    refund_amount_cents INTEGER NOT NULL,
    refund_reason VARCHAR(50) NOT NULL, -- customer_request, fraudulent, duplicate, host_cancellation
    refund_description TEXT,
    
    -- Refund processing
    refund_status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, succeeded, failed, canceled
    processed_by UUID REFERENCES users(id), -- Admin or system
    
    -- Fee handling
    platform_fee_refunded_cents INTEGER DEFAULT 0,
    host_fee_refunded_cents INTEGER DEFAULT 0,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    processed_at TIMESTAMP WITH TIME ZONE,
    
    INDEX idx_refunds_transaction (original_transaction_id),
    INDEX idx_refunds_status (refund_status, created_at DESC),
    INDEX idx_refunds_stripe (stripe_refund_id)
);
```

## Revenue Sharing & Payouts Schema

### Host Earnings Table
```sql
CREATE TABLE host_earnings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    host_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    activity_id UUID REFERENCES activities(id),
    transaction_id UUID REFERENCES transactions(id),
    
    -- Earnings breakdown
    gross_earnings_cents INTEGER NOT NULL,
    platform_fee_cents INTEGER NOT NULL,
    net_earnings_cents INTEGER NOT NULL,
    
    -- Earnings metadata
    earnings_date DATE NOT NULL DEFAULT CURRENT_DATE,
    earnings_type VARCHAR(20) NOT NULL, -- activity_payment, bonus, referral, premium_feature
    
    -- Payout tracking
    payout_status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, included_in_payout, paid
    payout_id UUID REFERENCES payouts(id),
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_host_earnings_host (host_id, earnings_date DESC),
    INDEX idx_host_earnings_activity (activity_id),
    INDEX idx_host_earnings_payout_status (payout_status, created_at),
    INDEX idx_host_earnings_date (earnings_date DESC)
);

-- Materialized view for host earnings summary
CREATE MATERIALIZED VIEW host_earnings_summary AS
SELECT 
    host_id,
    DATE_TRUNC('month', earnings_date) as month,
    COUNT(*) as transaction_count,
    SUM(gross_earnings_cents) as total_gross_cents,
    SUM(platform_fee_cents) as total_fees_cents,
    SUM(net_earnings_cents) as total_net_cents,
    AVG(net_earnings_cents) as avg_earnings_cents
FROM host_earnings
GROUP BY host_id, DATE_TRUNC('month', earnings_date);

CREATE UNIQUE INDEX idx_host_earnings_summary_unique ON host_earnings_summary(host_id, month);
```

### Payouts Table
```sql
CREATE TABLE payouts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    host_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    stripe_transfer_id VARCHAR(255),
    
    -- Payout details
    payout_amount_cents INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    payout_method VARCHAR(20) NOT NULL, -- bank_transfer, debit_card, paypal
    
    -- Payout period
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    earnings_count INTEGER NOT NULL, -- Number of earnings included
    
    -- Payout processing
    payout_status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, processing, paid, failed, canceled
    payout_schedule VARCHAR(20) NOT NULL DEFAULT 'weekly', -- daily, weekly, monthly, manual
    
    -- Bank details (for display only)
    bank_account_last_four VARCHAR(4),
    bank_name VARCHAR(100),
    
    -- Processing details
    initiated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    processed_at TIMESTAMP WITH TIME ZONE,
    failed_at TIMESTAMP WITH TIME ZONE,
    failure_reason TEXT,
    
    INDEX idx_payouts_host (host_id, initiated_at DESC),
    INDEX idx_payouts_status (payout_status, initiated_at DESC),
    INDEX idx_payouts_period (period_start, period_end),
    INDEX idx_payouts_stripe (stripe_transfer_id)
);

-- Update host earnings when payout is processed
CREATE OR REPLACE FUNCTION update_earnings_payout_status()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.payout_status = 'paid' AND OLD.payout_status != 'paid' THEN
        UPDATE host_earnings 
        SET payout_status = 'paid', payout_id = NEW.id
        WHERE host_id = NEW.host_id 
        AND earnings_date BETWEEN NEW.period_start AND NEW.period_end
        AND payout_status = 'pending';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_earnings_payout_status
    AFTER UPDATE ON payouts
    FOR EACH ROW EXECUTE FUNCTION update_earnings_payout_status();
```

## Subscription & Premium Features Schema

### Subscription Plans Table
```sql
CREATE TABLE subscription_plans (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    plan_name VARCHAR(50) NOT NULL UNIQUE,
    plan_tier VARCHAR(20) NOT NULL, -- free, pro, premium, enterprise
    
    -- Pricing
    price_cents INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    billing_interval VARCHAR(20) NOT NULL, -- month, year
    billing_interval_count INTEGER NOT NULL DEFAULT 1,
    
    -- Trial settings
    trial_period_days INTEGER DEFAULT 0,
    
    -- Plan features
    features JSONB NOT NULL DEFAULT '{}',
    feature_limits JSONB NOT NULL DEFAULT '{}',
    
    -- Plan metadata
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    is_public BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    
    -- Stripe integration
    stripe_price_id VARCHAR(255),
    stripe_product_id VARCHAR(255),
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_subscription_plans_tier (plan_tier, sort_order),
    INDEX idx_subscription_plans_active (is_active, is_public),
    INDEX idx_subscription_plans_stripe (stripe_price_id)
);

-- Insert default subscription plans
INSERT INTO subscription_plans (plan_name, plan_tier, price_cents, billing_interval, features, feature_limits) VALUES
('Free', 'free', 0, 'month', 
 '{"basic_activities": true, "basic_discovery": true, "basic_social": true}',
 '{"activities_per_month": 3, "rsvps_per_month": 10, "communities": 2}'),
('Pro', 'pro', 999, 'month',
 '{"unlimited_activities": true, "advanced_analytics": true, "priority_support": true, "reduced_fees": true}',
 '{"platform_fee_reduction": 0.02, "premium_listings": 5, "advanced_features": true}'),
('Premium', 'premium', 2999, 'month',
 '{"all_pro_features": true, "white_label": true, "api_access": true, "dedicated_support": true}',
 '{"platform_fee_reduction": 0.05, "unlimited_premium_listings": true, "custom_branding": true}');
```

### User Subscriptions Table
```sql
CREATE TABLE user_subscriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_plan_id UUID NOT NULL REFERENCES subscription_plans(id),
    
    -- Stripe subscription details
    stripe_subscription_id VARCHAR(255) UNIQUE,
    stripe_customer_id VARCHAR(255),
    
    -- Subscription status
    subscription_status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, trialing, past_due, canceled, unpaid
    
    -- Billing details
    current_period_start TIMESTAMP WITH TIME ZONE NOT NULL,
    current_period_end TIMESTAMP WITH TIME ZONE NOT NULL,
    trial_start TIMESTAMP WITH TIME ZONE,
    trial_end TIMESTAMP WITH TIME ZONE,
    
    -- Cancellation details
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    canceled_at TIMESTAMP WITH TIME ZONE,
    cancellation_reason VARCHAR(50),
    
    -- Usage tracking
    usage_data JSONB DEFAULT '{}',
    last_usage_reset TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_user_subscriptions_user (user_id),
    INDEX idx_user_subscriptions_status (subscription_status, current_period_end),
    INDEX idx_user_subscriptions_stripe (stripe_subscription_id),
    INDEX idx_user_subscriptions_trial (trial_end) WHERE trial_end IS NOT NULL
);

-- Function to check feature access
CREATE OR REPLACE FUNCTION check_feature_access(
    p_user_id UUID,
    p_feature_name VARCHAR,
    p_usage_amount INTEGER DEFAULT 1
)
RETURNS BOOLEAN AS $$
DECLARE
    user_plan RECORD;
    current_usage INTEGER;
    usage_limit INTEGER;
BEGIN
    -- Get user's current subscription plan
    SELECT sp.features, sp.feature_limits, us.usage_data
    INTO user_plan
    FROM user_subscriptions us
    JOIN subscription_plans sp ON us.subscription_plan_id = sp.id
    WHERE us.user_id = p_user_id 
    AND us.subscription_status IN ('active', 'trialing')
    ORDER BY us.created_at DESC
    LIMIT 1;
    
    -- If no subscription found, check free plan
    IF user_plan IS NULL THEN
        SELECT features, feature_limits, '{}'::jsonb as usage_data
        INTO user_plan
        FROM subscription_plans
        WHERE plan_tier = 'free' AND is_active = TRUE
        LIMIT 1;
    END IF;
    
    -- Check if feature is included in plan
    IF NOT (user_plan.features ? p_feature_name) THEN
        RETURN FALSE;
    END IF;
    
    -- Check usage limits if applicable
    IF user_plan.feature_limits ? p_feature_name THEN
        usage_limit := (user_plan.feature_limits->p_feature_name)::INTEGER;
        current_usage := COALESCE((user_plan.usage_data->p_feature_name)::INTEGER, 0);
        
        IF current_usage + p_usage_amount > usage_limit THEN
            RETURN FALSE;
        END IF;
    END IF;
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;
```

## Marketplace & Monetization Tools Schema

### Pricing Strategies Table
```sql
CREATE TABLE pricing_strategies (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    host_id UUID NOT NULL REFERENCES users(id),
    
    -- Pricing strategy type
    strategy_type VARCHAR(30) NOT NULL, -- fixed, dynamic, tiered, auction, pay_what_you_want
    
    -- Base pricing
    base_price_cents INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    
    -- Dynamic pricing settings
    dynamic_pricing_enabled BOOLEAN DEFAULT FALSE,
    demand_multiplier FLOAT DEFAULT 1.0,
    time_based_pricing JSONB DEFAULT '{}', -- Different prices by time/date
    capacity_based_pricing JSONB DEFAULT '{}', -- Price changes based on capacity
    
    -- Tiered pricing
    tier_pricing JSONB DEFAULT '[]', -- Array of {min_quantity, price_cents}
    
    -- Promotional pricing
    early_bird_price_cents INTEGER,
    early_bird_deadline TIMESTAMP WITH TIME ZONE,
    group_discount_enabled BOOLEAN DEFAULT FALSE,
    group_discount_threshold INTEGER,
    group_discount_percentage FLOAT,
    
    -- Auction settings (for auction-type activities)
    starting_bid_cents INTEGER,
    reserve_price_cents INTEGER,
    bid_increment_cents INTEGER,
    auction_end_time TIMESTAMP WITH TIME ZONE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_pricing_strategies_activity (activity_id),
    INDEX idx_pricing_strategies_host (host_id),
    INDEX idx_pricing_strategies_type (strategy_type)
);
```

### Discount Codes Table
```sql
CREATE TABLE discount_codes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) NOT NULL UNIQUE,
    created_by UUID NOT NULL REFERENCES users(id),
    
    -- Discount details
    discount_type VARCHAR(20) NOT NULL, -- percentage, fixed_amount, free
    discount_value INTEGER NOT NULL, -- Percentage (1-100) or amount in cents
    max_discount_cents INTEGER, -- Maximum discount for percentage types
    
    -- Usage limits
    usage_limit INTEGER, -- NULL for unlimited
    usage_count INTEGER DEFAULT 0,
    per_user_limit INTEGER DEFAULT 1,
    
    -- Validity period
    valid_from TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    valid_until TIMESTAMP WITH TIME ZONE,
    
    -- Applicability
    applicable_to VARCHAR(20) DEFAULT 'all', -- all, specific_activities, specific_hosts, specific_categories
    applicable_activity_ids UUID[],
    applicable_host_ids UUID[],
    applicable_categories TEXT[],
    minimum_purchase_cents INTEGER DEFAULT 0,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_discount_codes_code (code),
    INDEX idx_discount_codes_creator (created_by),
    INDEX idx_discount_codes_validity (valid_from, valid_until),
    INDEX idx_discount_codes_active (is_active, valid_until)
);

-- Track discount code usage
CREATE TABLE discount_code_usage (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    discount_code_id UUID NOT NULL REFERENCES discount_codes(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id),
    transaction_id UUID NOT NULL REFERENCES transactions(id),
    activity_id UUID REFERENCES activities(id),
    
    discount_amount_cents INTEGER NOT NULL,
    
    used_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(discount_code_id, user_id, activity_id),
    INDEX idx_discount_usage_code (discount_code_id, used_at DESC),
    INDEX idx_discount_usage_user (user_id, used_at DESC)
);
```

### Affiliate Program Schema
```sql
CREATE TABLE affiliate_programs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    program_name VARCHAR(100) NOT NULL,
    created_by UUID NOT NULL REFERENCES users(id),
    
    -- Commission structure
    commission_type VARCHAR(20) NOT NULL, -- percentage, fixed_amount, tiered
    commission_rate FLOAT NOT NULL, -- Percentage (0.01 = 1%) or fixed amount in cents
    tiered_rates JSONB DEFAULT '[]', -- For tiered commission structures
    
    -- Program settings
    cookie_duration_days INTEGER DEFAULT 30,
    minimum_payout_cents INTEGER DEFAULT 5000, -- $50 minimum
    payment_schedule VARCHAR(20) DEFAULT 'monthly', -- weekly, monthly, quarterly
    
    -- Eligibility
    is_public BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    minimum_followers INTEGER DEFAULT 0,
    
    -- Program status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_affiliate_programs_creator (created_by),
    INDEX idx_affiliate_programs_public (is_public, is_active)
);

CREATE TABLE affiliate_links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    affiliate_program_id UUID NOT NULL REFERENCES affiliate_programs(id) ON DELETE CASCADE,
    affiliate_user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Link details
    tracking_code VARCHAR(50) NOT NULL UNIQUE,
    target_url TEXT NOT NULL,
    custom_landing_page_url TEXT,
    
    -- Performance tracking
    click_count INTEGER DEFAULT 0,
    conversion_count INTEGER DEFAULT 0,
    total_commission_cents INTEGER DEFAULT 0,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_affiliate_links_program (affiliate_program_id),
    INDEX idx_affiliate_links_affiliate (affiliate_user_id),
    INDEX idx_affiliate_links_tracking (tracking_code)
);

CREATE TABLE affiliate_conversions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    affiliate_link_id UUID NOT NULL REFERENCES affiliate_links(id),
    transaction_id UUID NOT NULL REFERENCES transactions(id),
    
    commission_amount_cents INTEGER NOT NULL,
    commission_status VARCHAR(20) DEFAULT 'pending', -- pending, approved, paid, rejected
    
    converted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    approved_at TIMESTAMP WITH TIME ZONE,
    paid_at TIMESTAMP WITH TIME ZONE,
    
    INDEX idx_affiliate_conversions_link (affiliate_link_id, converted_at DESC),
    INDEX idx_affiliate_conversions_transaction (transaction_id),
    INDEX idx_affiliate_conversions_status (commission_status, converted_at DESC)
);
```

## Financial Analytics Schema

### Revenue Analytics Table
```sql
CREATE TABLE revenue_analytics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    analytics_date DATE NOT NULL DEFAULT CURRENT_DATE,
    
    -- Platform revenue
    total_revenue_cents INTEGER DEFAULT 0,
    platform_fee_revenue_cents INTEGER DEFAULT 0,
    subscription_revenue_cents INTEGER DEFAULT 0,
    
    -- Transaction metrics
    transaction_count INTEGER DEFAULT 0,
    successful_transactions INTEGER DEFAULT 0,
    failed_transactions INTEGER DEFAULT 0,
    refunded_transactions INTEGER DEFAULT 0,
    
    -- Host metrics
    total_host_earnings_cents INTEGER DEFAULT 0,
    active_earning_hosts INTEGER DEFAULT 0,
    new_earning_hosts INTEGER DEFAULT 0,
    
    -- Subscription metrics
    new_subscriptions INTEGER DEFAULT 0,
    canceled_subscriptions INTEGER DEFAULT 0,
    active_subscriptions INTEGER DEFAULT 0,
    subscription_churn_rate FLOAT DEFAULT 0,
    
    -- Geographic breakdown
    revenue_by_country JSONB DEFAULT '{}',
    
    UNIQUE(analytics_date),
    INDEX idx_revenue_analytics_date (analytics_date DESC)
);

-- Function to update daily revenue analytics
CREATE OR REPLACE FUNCTION update_daily_revenue_analytics(target_date DATE DEFAULT CURRENT_DATE)
RETURNS void AS $$
BEGIN
    INSERT INTO revenue_analytics (
        analytics_date,
        total_revenue_cents,
        platform_fee_revenue_cents,
        transaction_count,
        successful_transactions,
        failed_transactions,
        total_host_earnings_cents
    )
    SELECT 
        target_date,
        COALESCE(SUM(amount_cents), 0),
        COALESCE(SUM(platform_fee_cents), 0),
        COUNT(*),
        COUNT(*) FILTER (WHERE payment_status = 'succeeded'),
        COUNT(*) FILTER (WHERE payment_status = 'failed'),
        COALESCE(SUM(host_earnings_cents), 0)
    FROM transactions
    WHERE DATE(created_at) = target_date
    ON CONFLICT (analytics_date) DO UPDATE SET
        total_revenue_cents = EXCLUDED.total_revenue_cents,
        platform_fee_revenue_cents = EXCLUDED.platform_fee_revenue_cents,
        transaction_count = EXCLUDED.transaction_count,
        successful_transactions = EXCLUDED.successful_transactions,
        failed_transactions = EXCLUDED.failed_transactions,
        total_host_earnings_cents = EXCLUDED.total_host_earnings_cents;
END;
$$ LANGUAGE plpgsql;

-- Schedule daily analytics update
SELECT cron.schedule('update-revenue-analytics', '0 1 * * *', 'SELECT update_daily_revenue_analytics();');
```

---

**Database Schema Status**: ✅ Complete - Payment and monetization data structures defined
**Next Steps**: Define service architecture for payment processing and monetization
