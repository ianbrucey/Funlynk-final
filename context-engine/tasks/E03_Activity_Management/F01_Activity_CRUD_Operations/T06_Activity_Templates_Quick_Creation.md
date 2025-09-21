# T06 Activity Templates & Quick Creation

## Problem Definition

### Task Overview
Implement a comprehensive activity template system that enables hosts to create activities quickly using pre-defined templates, save their own activities as templates, and access community-shared templates. This system reduces activity creation time while maintaining quality and consistency.

### Problem Statement
Activity hosts need efficient creation tools to:
- **Reduce creation time**: Use templates to quickly create common activity types
- **Maintain consistency**: Ensure similar activities follow established patterns
- **Share best practices**: Learn from successful activity formats
- **Customize efficiently**: Modify templates while preserving core structure
- **Scale hosting**: Manage multiple similar activities with minimal effort

The template system must balance standardization with customization flexibility.

### Scope
**In Scope:**
- Template creation and management system
- Pre-built templates for common activity types
- Personal template library for hosts
- Community template sharing (curated)
- Quick creation workflow using templates
- Template customization and modification
- Template analytics and usage tracking

**Out of Scope:**
- Advanced template marketplace with payments (future enhancement)
- AI-generated templates (future enhancement)
- Complex template versioning (basic versioning only)
- Template collaboration features (handled by E05)

### Success Criteria
- [ ] Template usage reduces activity creation time by 50%+
- [ ] 40%+ of activities created using templates
- [ ] Template library achieves 90%+ host satisfaction
- [ ] Quick creation flow completes in under 60 seconds
- [ ] Template customization maintains 95%+ data integrity
- [ ] Community templates drive 20%+ of template usage

### Dependencies
- **Requires**: T02 Activity management APIs for template operations
- **Requires**: T03 Activity creation components for template integration
- **Requires**: T05 Activity editing for template modification
- **Requires**: F03 Tagging system for template categorization
- **Blocks**: Efficient activity creation workflows
- **Informs**: E04 Discovery (template-based activity recommendations)

### Acceptance Criteria

#### Template Management System
- [ ] Template creation from existing activities
- [ ] Template editing and customization interface
- [ ] Template organization with categories and tags
- [ ] Personal template library management
- [ ] Template sharing and privacy controls

#### Pre-built Template Library
- [ ] Comprehensive templates for common activity types
- [ ] Template validation and quality assurance
- [ ] Template metadata (usage stats, ratings, categories)
- [ ] Template search and filtering capabilities
- [ ] Template preview with example activities

#### Quick Creation Workflow
- [ ] Template selection interface with preview
- [ ] One-click template application with smart defaults
- [ ] Rapid customization of template fields
- [ ] Template-based activity creation in under 60 seconds
- [ ] Seamless integration with standard creation flow

#### Template Customization
- [ ] Field-level template modification
- [ ] Template inheritance and variation creation
- [ ] Custom template creation from scratch
- [ ] Template validation and error prevention
- [ ] Template versioning and update management

#### Community Features
- [ ] Curated community template collection
- [ ] Template rating and review system
- [ ] Template usage analytics and trending
- [ ] Template sharing with attribution
- [ ] Quality moderation for shared templates

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Template Data Model & APIs** (90 minutes)
   - Design template database schema and relationships
   - Implement template CRUD operations
   - Create template application and customization logic
   - Add template analytics and usage tracking

2. **Template Management Interface** (120 minutes)
   - Build template library and browsing interface
   - Create template creation and editing components
   - Implement quick creation workflow
   - Add template search and filtering

3. **Community & Integration** (60 minutes)
   - Create community template curation system
   - Integrate templates with activity creation flow
   - Add template analytics and performance tracking
   - Implement template sharing and privacy controls

### Deliverables
- [ ] Template management system with CRUD operations
- [ ] Pre-built template library for common activities
- [ ] Quick creation workflow with template selection
- [ ] Template customization and editing interface
- [ ] Community template sharing and curation
- [ ] Template analytics and usage tracking
- [ ] Integration with activity creation and editing flows
- [ ] Template validation and quality assurance
- [ ] Comprehensive testing and performance optimization

### Technical Specifications

#### Template Data Model
```sql
-- Activity templates table
CREATE TABLE activity_templates (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL,
  description TEXT,
  category_id UUID REFERENCES categories(id),
  created_by UUID REFERENCES users(id),
  is_public BOOLEAN DEFAULT false,
  is_featured BOOLEAN DEFAULT false,
  is_system_template BOOLEAN DEFAULT false,
  usage_count INTEGER DEFAULT 0,
  rating_average DECIMAL(3,2) DEFAULT 0,
  rating_count INTEGER DEFAULT 0,
  template_data JSONB NOT NULL,
  preview_image_url VARCHAR(255),
  tags TEXT[],
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Template usage analytics
CREATE TABLE template_usage_analytics (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  template_id UUID REFERENCES activity_templates(id) ON DELETE CASCADE,
  user_id UUID REFERENCES users(id) ON DELETE SET NULL,
  activity_id UUID REFERENCES activities(id) ON DELETE SET NULL,
  usage_type VARCHAR(20) NOT NULL, -- 'applied', 'customized', 'saved'
  customizations JSONB,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Template ratings
CREATE TABLE template_ratings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  template_id UUID REFERENCES activity_templates(id) ON DELETE CASCADE,
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  rating INTEGER CHECK (rating >= 1 AND rating <= 5),
  review TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  UNIQUE(template_id, user_id)
);
```

#### Template Structure
```typescript
interface ActivityTemplate {
  id: string;
  name: string;
  description: string;
  category_id?: string;
  created_by: string;
  is_public: boolean;
  is_featured: boolean;
  is_system_template: boolean;
  usage_count: number;
  rating_average: number;
  rating_count: number;
  template_data: TemplateData;
  preview_image_url?: string;
  tags: string[];
  created_at: Date;
  updated_at: Date;
}

interface TemplateData {
  // Core activity fields with template values
  title_template: string;
  description_template: string;
  duration_minutes?: number;
  capacity?: number;
  skill_level?: string;
  age_restriction?: string;
  equipment_needed?: string;
  requirements?: string;
  
  // Template-specific metadata
  customizable_fields: string[];
  required_fields: string[];
  default_tags: string[];
  suggested_categories: string[];
  
  // Template instructions
  host_instructions?: string;
  customization_tips?: string;
}
```

#### Template Service
```typescript
class ActivityTemplateService {
  async createTemplate(
    activityId: string,
    templateData: CreateTemplateRequest
  ): Promise<ActivityTemplate> {
    // Extract template data from existing activity
    const activity = await this.getActivity(activityId);
    const template = this.convertActivityToTemplate(activity, templateData);
    
    // Save template
    const { data, error } = await supabase
      .from('activity_templates')
      .insert(template)
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  async applyTemplate(
    templateId: string,
    customizations: TemplateCustomizations
  ): Promise<ActivityCreateRequest> {
    const template = await this.getTemplate(templateId);
    
    // Apply template data with customizations
    const activityData = this.mergeTemplateWithCustomizations(
      template.template_data,
      customizations
    );
    
    // Track template usage
    await this.trackTemplateUsage(templateId, 'applied', customizations);
    
    return activityData;
  }
  
  async getTemplatesByCategory(categoryId: string): Promise<ActivityTemplate[]> {
    const { data, error } = await supabase
      .from('activity_templates')
      .select('*')
      .eq('category_id', categoryId)
      .eq('is_public', true)
      .order('usage_count', { ascending: false });
    
    if (error) throw error;
    return data;
  }
  
  async getFeaturedTemplates(): Promise<ActivityTemplate[]> {
    const { data, error } = await supabase
      .from('activity_templates')
      .select('*')
      .eq('is_featured', true)
      .eq('is_public', true)
      .order('rating_average', { ascending: false })
      .limit(10);
    
    if (error) throw error;
    return data;
  }
  
  private convertActivityToTemplate(
    activity: Activity,
    templateData: CreateTemplateRequest
  ): Omit<ActivityTemplate, 'id' | 'created_at' | 'updated_at'> {
    return {
      name: templateData.name,
      description: templateData.description,
      category_id: activity.category_id,
      created_by: activity.host_id,
      is_public: templateData.is_public || false,
      is_featured: false,
      is_system_template: false,
      usage_count: 0,
      rating_average: 0,
      rating_count: 0,
      template_data: {
        title_template: activity.title,
        description_template: activity.description,
        duration_minutes: this.calculateDuration(activity.start_time, activity.end_time),
        capacity: activity.capacity,
        skill_level: activity.skill_level,
        age_restriction: activity.age_restriction,
        equipment_needed: activity.equipment_needed,
        requirements: activity.requirements,
        customizable_fields: templateData.customizable_fields,
        required_fields: templateData.required_fields,
        default_tags: [], // Extract from activity tags
        suggested_categories: [activity.category_id].filter(Boolean),
        host_instructions: templateData.host_instructions,
        customization_tips: templateData.customization_tips,
      },
      preview_image_url: templateData.preview_image_url,
      tags: templateData.tags || [],
    };
  }
}
```

#### Quick Creation Interface
```typescript
interface TemplateQuickCreateProps {
  onActivityCreated: (activity: Activity) => void;
  onCancel: () => void;
}

const TemplateQuickCreate: React.FC<TemplateQuickCreateProps> = ({
  onActivityCreated,
  onCancel,
}) => {
  const [selectedTemplate, setSelectedTemplate] = useState<ActivityTemplate>();
  const [customizations, setCustomizations] = useState<TemplateCustomizations>({});
  const [step, setStep] = useState<'select' | 'customize' | 'preview'>('select');
  
  const handleTemplateSelect = (template: ActivityTemplate) => {
    setSelectedTemplate(template);
    setStep('customize');
  };
  
  const handleCustomizationComplete = (customizations: TemplateCustomizations) => {
    setCustomizations(customizations);
    setStep('preview');
  };
  
  const handleCreateActivity = async () => {
    if (!selectedTemplate) return;
    
    try {
      // Apply template with customizations
      const activityData = await templateService.applyTemplate(
        selectedTemplate.id,
        customizations
      );
      
      // Create activity
      const activity = await activityService.createActivity(activityData);
      onActivityCreated(activity);
    } catch (error) {
      // Handle creation error
    }
  };
  
  return (
    <View style={styles.container}>
      {step === 'select' && (
        <TemplateSelector
          onSelect={handleTemplateSelect}
          onCancel={onCancel}
        />
      )}
      
      {step === 'customize' && selectedTemplate && (
        <TemplateCustomizer
          template={selectedTemplate}
          onComplete={handleCustomizationComplete}
          onBack={() => setStep('select')}
        />
      )}
      
      {step === 'preview' && selectedTemplate && (
        <TemplatePreview
          template={selectedTemplate}
          customizations={customizations}
          onEdit={() => setStep('customize')}
          onCreate={handleCreateActivity}
        />
      )}
    </View>
  );
};
```

#### System Templates
```typescript
// Pre-built system templates for common activities
const SYSTEM_TEMPLATES: Partial<ActivityTemplate>[] = [
  {
    name: 'Basketball Pickup Game',
    description: 'Casual basketball game for players of all skill levels',
    template_data: {
      title_template: 'Basketball Pickup Game at {location}',
      description_template: 'Join us for a fun basketball game! Bring your energy and we\'ll bring the competition. All skill levels welcome.',
      duration_minutes: 120,
      capacity: 10,
      skill_level: 'all',
      equipment_needed: 'Basketball shoes, water bottle',
      requirements: 'Basic basketball knowledge helpful but not required',
      customizable_fields: ['title_template', 'capacity', 'skill_level'],
      required_fields: ['location', 'start_time'],
      default_tags: ['basketball', 'sports', 'pickup-game', 'outdoor'],
      host_instructions: 'Make sure to confirm court availability and bring a basketball',
    },
    tags: ['sports', 'basketball', 'outdoor', 'team-sport'],
    is_system_template: true,
    is_public: true,
    is_featured: true,
  },
  
  {
    name: 'Coffee & Networking',
    description: 'Professional networking meetup over coffee',
    template_data: {
      title_template: 'Coffee & Networking - {topic}',
      description_template: 'Join fellow professionals for coffee and meaningful connections. Great opportunity to expand your network and share experiences.',
      duration_minutes: 90,
      capacity: 15,
      skill_level: 'all',
      age_restriction: '18+',
      requirements: 'Bring business cards if you have them',
      customizable_fields: ['title_template', 'topic', 'capacity'],
      required_fields: ['location', 'start_time', 'topic'],
      default_tags: ['networking', 'coffee', 'professional', 'business'],
      host_instructions: 'Choose a coffee shop with enough seating and good atmosphere for conversation',
    },
    tags: ['networking', 'professional', 'coffee', 'business'],
    is_system_template: true,
    is_public: true,
    is_featured: true,
  },
  
  // Additional system templates...
];
```

### Quality Checklist
- [ ] Template system reduces activity creation time significantly
- [ ] Template data integrity maintained through validation
- [ ] Community templates are properly moderated
- [ ] Template customization is intuitive and flexible
- [ ] Template analytics provide valuable insights
- [ ] Performance optimized for template browsing and application
- [ ] Template sharing respects privacy and attribution
- [ ] Integration with activity creation flow is seamless

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E03 Activity Management  
**Feature**: F01 Activity CRUD Operations  
**Dependencies**: T02 Activity APIs, T03 Creation Components, T05 Activity Editing, F03 Tagging System  
**Blocks**: Efficient Activity Creation Workflows
