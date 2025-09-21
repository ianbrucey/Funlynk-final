# T02 Payment Backend Infrastructure & Stripe Integration

## Problem Definition

### Task Overview
Implement comprehensive backend payment processing infrastructure with Stripe Connect integration, supporting activity payments, subscriptions, marketplace transactions, and multi-currency processing. This includes building secure, scalable payment systems that handle complex financial workflows.

### Problem Statement
The platform needs robust payment infrastructure to:
- **Process payments securely**: Handle all transaction types with PCI compliance
- **Support marketplace functionality**: Enable host payouts and platform fees
- **Manage subscriptions**: Handle recurring billing and subscription lifecycle
- **Scale with growth**: Support increasing transaction volume without degradation
- **Ensure reliability**: Provide 99.9%+ uptime for payment processing

### Scope
**In Scope:**
- Stripe Connect integration for marketplace payments
- Activity payment processing and RSVP transactions
- Subscription billing and recurring payment management
- Payment method storage and tokenization
- Refund and cancellation processing
- Multi-currency support and international payments
- Payment webhook handling and event processing

**Out of Scope:**
- Frontend payment components (covered in T03)
- Payment security compliance (covered in T04)
- Revenue sharing calculations (handled by F02)
- Payment analytics dashboards (covered in T05)

### Success Criteria
- [ ] Payment processing handles 10K+ concurrent transactions
- [ ] Payment success rate above 98% for all transaction types
- [ ] Payment processing time under 3 seconds for 95% of transactions
- [ ] Stripe webhook processing achieves 99.9% reliability
- [ ] Multi-currency support for 20+ major currencies
- [ ] Payment system maintains 99.9% uptime

### Dependencies
- **Requires**: E01 Database infrastructure for payment data storage
- **Requires**: E02 User profiles for payment account management
- **Requires**: E03 Activity data for payment processing
- **Requires**: Stripe Connect account and API access
- **Blocks**: T03 Frontend implementation needs payment APIs
- **Blocks**: T04 Security systems need payment infrastructure

### Acceptance Criteria

#### Stripe Connect Integration
- [ ] Stripe Connect onboarding for hosts and marketplace functionality
- [ ] Payment processing with automatic platform fee collection
- [ ] Host payout management with Stripe Express accounts
- [ ] Multi-party payment splitting and fee calculation
- [ ] Stripe webhook handling for payment status updates

#### Payment Processing APIs
- [ ] Activity payment processing with RSVP integration
- [ ] Subscription billing and recurring payment management
- [ ] Payment method storage and tokenization
- [ ] Refund and cancellation processing
- [ ] Payment status tracking and updates

#### Transaction Management
- [ ] Comprehensive transaction logging and audit trails
- [ ] Payment reconciliation and settlement tracking
- [ ] Failed payment retry mechanisms and recovery
- [ ] Dispute and chargeback handling
- [ ] Payment notification and confirmation systems

#### Multi-Currency Support
- [ ] Currency conversion and exchange rate management
- [ ] International payment processing and compliance
- [ ] Currency-specific payment method support
- [ ] Localized payment experiences by region
- [ ] Currency hedging and risk management

#### Performance & Reliability
- [ ] High-throughput payment processing optimization
- [ ] Payment system monitoring and alerting
- [ ] Graceful error handling and recovery mechanisms
- [ ] Payment data backup and disaster recovery
- [ ] Load balancing and horizontal scaling support

### Estimated Effort
**4 hours** for experienced backend developer

### Task Breakdown
1. **Stripe Integration & Setup** (120 minutes)
   - Set up Stripe Connect for marketplace functionality
   - Implement payment processing APIs and webhook handling
   - Create host onboarding and account management
   - Add payment method storage and tokenization

2. **Transaction Processing & Management** (90 minutes)
   - Build activity payment and subscription billing systems
   - Implement refund and cancellation processing
   - Create transaction logging and audit systems
   - Add multi-currency support and conversion

3. **Performance & Monitoring** (30 minutes)
   - Implement payment system monitoring and alerting
   - Add performance optimization and caching
   - Create error handling and recovery mechanisms
   - Build comprehensive testing and validation

### Deliverables
- [ ] Stripe Connect integration with marketplace functionality
- [ ] Payment processing APIs for all transaction types
- [ ] Subscription billing and recurring payment management
- [ ] Payment method storage and tokenization system
- [ ] Refund and cancellation processing system
- [ ] Multi-currency support and international payments
- [ ] Payment webhook handling and event processing
- [ ] Transaction logging and audit trail system
- [ ] Payment system monitoring and alerting

### Technical Specifications

#### Stripe Connect Integration
```typescript
interface StripeConnectAccount {
  id: string;
  userId: string;
  stripeAccountId: string;
  accountType: 'express' | 'standard' | 'custom';
  onboardingComplete: boolean;
  payoutsEnabled: boolean;
  chargesEnabled: boolean;
  requirements: StripeAccountRequirement[];
  createdAt: Date;
  updatedAt: Date;
}

class StripeConnectService {
  private stripe: Stripe;
  
  constructor() {
    this.stripe = new Stripe(process.env.STRIPE_SECRET_KEY!, {
      apiVersion: '2023-10-16',
    });
  }
  
  async createConnectAccount(
    userId: string,
    accountType: 'express' | 'standard' = 'express'
  ): Promise<StripeConnectAccount> {
    const user = await this.getUserById(userId);
    if (!user) {
      throw new Error('User not found');
    }
    
    // Create Stripe Connect account
    const stripeAccount = await this.stripe.accounts.create({
      type: accountType,
      country: user.country || 'US',
      email: user.email,
      capabilities: {
        card_payments: { requested: true },
        transfers: { requested: true },
      },
      business_type: 'individual',
      individual: {
        first_name: user.firstName,
        last_name: user.lastName,
        email: user.email,
      },
    });
    
    // Store account information
    const connectAccount: StripeConnectAccount = {
      id: generateId(),
      userId,
      stripeAccountId: stripeAccount.id,
      accountType,
      onboardingComplete: false,
      payoutsEnabled: false,
      chargesEnabled: false,
      requirements: [],
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    
    await this.saveConnectAccount(connectAccount);
    
    return connectAccount;
  }
  
  async createOnboardingLink(
    userId: string,
    returnUrl: string,
    refreshUrl: string
  ): Promise<string> {
    const account = await this.getConnectAccount(userId);
    if (!account) {
      throw new Error('Connect account not found');
    }
    
    const accountLink = await this.stripe.accountLinks.create({
      account: account.stripeAccountId,
      refresh_url: refreshUrl,
      return_url: returnUrl,
      type: 'account_onboarding',
    });
    
    return accountLink.url;
  }
  
  async processMarketplacePayment(
    paymentRequest: MarketplacePaymentRequest
  ): Promise<PaymentResult> {
    const { amount, currency, hostUserId, activityId, paymentMethodId, applicationFeeAmount } = paymentRequest;
    
    // Get host's Connect account
    const hostAccount = await this.getConnectAccount(hostUserId);
    if (!hostAccount || !hostAccount.chargesEnabled) {
      throw new Error('Host account not ready for payments');
    }
    
    try {
      // Create payment intent with application fee
      const paymentIntent = await this.stripe.paymentIntents.create({
        amount: Math.round(amount * 100), // Convert to cents
        currency: currency.toLowerCase(),
        payment_method: paymentMethodId,
        application_fee_amount: Math.round(applicationFeeAmount * 100),
        transfer_data: {
          destination: hostAccount.stripeAccountId,
        },
        metadata: {
          activityId,
          hostUserId,
          type: 'activity_payment',
        },
        confirm: true,
        return_url: `${process.env.BASE_URL}/payment/confirm`,
      });
      
      // Store transaction record
      await this.storeTransaction({
        paymentIntentId: paymentIntent.id,
        activityId,
        hostUserId,
        amount,
        currency,
        applicationFeeAmount,
        status: paymentIntent.status,
        createdAt: new Date(),
      });
      
      return {
        success: true,
        paymentIntentId: paymentIntent.id,
        status: paymentIntent.status,
        clientSecret: paymentIntent.client_secret,
      };
    } catch (error) {
      console.error('Payment processing failed:', error);
      throw new Error(`Payment failed: ${error.message}`);
    }
  }
}
```

#### Payment Processing Service
```typescript
interface PaymentTransaction {
  id: string;
  paymentIntentId: string;
  userId: string;
  activityId?: string;
  subscriptionId?: string;
  amount: number;
  currency: string;
  status: PaymentStatus;
  paymentMethod: PaymentMethodInfo;
  metadata: Record<string, any>;
  createdAt: Date;
  updatedAt: Date;
}

type PaymentStatus = 'pending' | 'processing' | 'succeeded' | 'failed' | 'canceled' | 'refunded';

class PaymentProcessingService {
  async processActivityPayment(
    paymentRequest: ActivityPaymentRequest
  ): Promise<PaymentResult> {
    const { userId, activityId, amount, currency, paymentMethodId } = paymentRequest;
    
    // Validate payment request
    await this.validatePaymentRequest(paymentRequest);
    
    // Get activity and host information
    const activity = await this.getActivity(activityId);
    if (!activity) {
      throw new Error('Activity not found');
    }
    
    // Calculate fees
    const fees = await this.calculateFees(amount, currency);
    
    // Process payment through Stripe Connect
    const paymentResult = await this.stripeConnectService.processMarketplacePayment({
      amount,
      currency,
      hostUserId: activity.hostId,
      activityId,
      paymentMethodId,
      applicationFeeAmount: fees.platformFee,
    });
    
    if (paymentResult.success) {
      // Create RSVP record
      await this.createRSVP({
        userId,
        activityId,
        status: 'confirmed',
        paymentTransactionId: paymentResult.paymentIntentId,
      });
      
      // Send confirmation notifications
      await this.sendPaymentConfirmation(userId, activityId, paymentResult);
    }
    
    return paymentResult;
  }
  
  async processSubscriptionPayment(
    subscriptionRequest: SubscriptionPaymentRequest
  ): Promise<SubscriptionResult> {
    const { userId, planId, paymentMethodId } = subscriptionRequest;
    
    // Get subscription plan
    const plan = await this.getSubscriptionPlan(planId);
    if (!plan) {
      throw new Error('Subscription plan not found');
    }
    
    // Create or update customer in Stripe
    const customer = await this.getOrCreateStripeCustomer(userId);
    
    // Attach payment method to customer
    await this.stripe.paymentMethods.attach(paymentMethodId, {
      customer: customer.id,
    });
    
    // Set as default payment method
    await this.stripe.customers.update(customer.id, {
      invoice_settings: {
        default_payment_method: paymentMethodId,
      },
    });
    
    // Create subscription
    const subscription = await this.stripe.subscriptions.create({
      customer: customer.id,
      items: [{ price: plan.stripePriceId }],
      payment_behavior: 'default_incomplete',
      payment_settings: { save_default_payment_method: 'on_subscription' },
      expand: ['latest_invoice.payment_intent'],
    });
    
    // Store subscription record
    await this.storeSubscription({
      userId,
      planId,
      stripeSubscriptionId: subscription.id,
      status: subscription.status,
      currentPeriodStart: new Date(subscription.current_period_start * 1000),
      currentPeriodEnd: new Date(subscription.current_period_end * 1000),
    });
    
    return {
      subscriptionId: subscription.id,
      status: subscription.status,
      clientSecret: subscription.latest_invoice?.payment_intent?.client_secret,
    };
  }
  
  async processRefund(
    transactionId: string,
    refundAmount?: number,
    reason?: string
  ): Promise<RefundResult> {
    const transaction = await this.getTransaction(transactionId);
    if (!transaction) {
      throw new Error('Transaction not found');
    }
    
    const refundAmountCents = refundAmount ? 
      Math.round(refundAmount * 100) : 
      Math.round(transaction.amount * 100);
    
    try {
      const refund = await this.stripe.refunds.create({
        payment_intent: transaction.paymentIntentId,
        amount: refundAmountCents,
        reason: reason as any,
        metadata: {
          originalTransactionId: transactionId,
          refundReason: reason || 'requested_by_customer',
        },
      });
      
      // Update transaction status
      await this.updateTransactionStatus(transactionId, 'refunded');
      
      // Store refund record
      await this.storeRefund({
        transactionId,
        stripeRefundId: refund.id,
        amount: refundAmount || transaction.amount,
        currency: transaction.currency,
        reason,
        status: refund.status,
        createdAt: new Date(),
      });
      
      return {
        success: true,
        refundId: refund.id,
        amount: refund.amount / 100,
        status: refund.status,
      };
    } catch (error) {
      console.error('Refund processing failed:', error);
      throw new Error(`Refund failed: ${error.message}`);
    }
  }
}
```

#### Webhook Handler
```typescript
class StripeWebhookHandler {
  async handleWebhook(
    payload: string,
    signature: string
  ): Promise<void> {
    let event: Stripe.Event;
    
    try {
      event = this.stripe.webhooks.constructEvent(
        payload,
        signature,
        process.env.STRIPE_WEBHOOK_SECRET!
      );
    } catch (error) {
      console.error('Webhook signature verification failed:', error);
      throw new Error('Invalid webhook signature');
    }
    
    // Handle the event
    switch (event.type) {
      case 'payment_intent.succeeded':
        await this.handlePaymentSucceeded(event.data.object as Stripe.PaymentIntent);
        break;
      case 'payment_intent.payment_failed':
        await this.handlePaymentFailed(event.data.object as Stripe.PaymentIntent);
        break;
      case 'invoice.payment_succeeded':
        await this.handleSubscriptionPaymentSucceeded(event.data.object as Stripe.Invoice);
        break;
      case 'customer.subscription.updated':
        await this.handleSubscriptionUpdated(event.data.object as Stripe.Subscription);
        break;
      case 'account.updated':
        await this.handleAccountUpdated(event.data.object as Stripe.Account);
        break;
      default:
        console.log(`Unhandled event type: ${event.type}`);
    }
  }
  
  private async handlePaymentSucceeded(paymentIntent: Stripe.PaymentIntent): Promise<void> {
    // Update transaction status
    await this.updateTransactionStatus(paymentIntent.id, 'succeeded');
    
    // Send confirmation notifications
    const transaction = await this.getTransactionByPaymentIntent(paymentIntent.id);
    if (transaction) {
      await this.sendPaymentSuccessNotification(transaction);
    }
  }
  
  private async handleSubscriptionPaymentSucceeded(invoice: Stripe.Invoice): Promise<void> {
    if (invoice.subscription) {
      // Update subscription status
      await this.updateSubscriptionStatus(
        invoice.subscription as string,
        'active'
      );
      
      // Grant premium features
      const subscription = await this.getSubscriptionByStripeId(invoice.subscription as string);
      if (subscription) {
        await this.grantPremiumFeatures(subscription.userId);
      }
    }
  }
}
```

### Quality Checklist
- [ ] Stripe Connect integration handles marketplace payments correctly
- [ ] Payment processing APIs support all required transaction types
- [ ] Subscription billing handles complex scenarios and edge cases
- [ ] Multi-currency support works accurately with exchange rates
- [ ] Webhook handling is reliable and handles all relevant events
- [ ] Error handling provides clear feedback and recovery mechanisms
- [ ] Performance optimization handles high transaction volumes
- [ ] Security measures protect sensitive payment data

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Backend Developer  
**Epic**: E06 Payments & Monetization  
**Feature**: F01 Payment Processing System  
**Dependencies**: Database Infrastructure (E01), User Profiles (E02), Activity Data (E03), Stripe Connect  
**Blocks**: T03 Frontend Implementation, T04 Security Systems
