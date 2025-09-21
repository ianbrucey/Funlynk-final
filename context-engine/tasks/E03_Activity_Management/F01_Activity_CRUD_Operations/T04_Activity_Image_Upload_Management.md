# T04 Activity Image Upload & Management

## Problem Definition

### Task Overview
Implement comprehensive image handling for activities including upload, optimization, storage, and management. This includes both backend image processing services and frontend components for image selection, upload, and management within the activity creation and editing workflows.

### Problem Statement
Activity hosts need robust image capabilities to:
- **Enhance activity appeal**: Add compelling visuals that attract participants
- **Manage multiple images**: Upload, organize, and manage activity photo galleries
- **Ensure performance**: Optimize images for fast loading across devices
- **Handle failures gracefully**: Provide reliable upload with error recovery
- **Maintain quality**: Preserve image quality while optimizing file sizes

The system must balance image quality with performance while providing an intuitive user experience.

### Scope
**In Scope:**
- Image upload components with progress tracking
- Image optimization and processing (resize, compress, format conversion)
- Supabase Storage integration for secure image storage
- Image gallery management for activities
- Image validation and content moderation
- CDN integration for fast image delivery
- Offline image handling and sync

**Out of Scope:**
- Advanced image editing features (filters, cropping beyond basic)
- Video upload and processing (future enhancement)
- AI-powered image analysis (future enhancement)
- Social image sharing features (handled by E05)

### Success Criteria
- [ ] Image uploads achieve 98%+ success rate
- [ ] Upload progress provides accurate feedback to users
- [ ] Image optimization reduces file sizes by 60%+ without quality loss
- [ ] Image loading times under 2 seconds on mobile networks
- [ ] Gallery management is intuitive and efficient
- [ ] System handles 1000+ concurrent image uploads

### Dependencies
- **Requires**: T02 Activity management APIs for image association
- **Requires**: T03 Activity creation components for integration
- **Requires**: Supabase Storage configuration and CDN setup
- **Requires**: E01.F01 Database schema for image metadata
- **Blocks**: Complete activity creation workflow
- **Informs**: T05 Activity editing (image management in edit flow)

### Acceptance Criteria

#### Image Upload Components
- [ ] Intuitive image selection from camera or gallery
- [ ] Multiple image upload with batch processing
- [ ] Real-time upload progress with cancellation support
- [ ] Image preview before and after upload
- [ ] Drag-and-drop support for web interface

#### Image Processing & Optimization
- [ ] Automatic image resizing for different use cases (thumbnail, card, full)
- [ ] Image compression with quality preservation
- [ ] Format optimization (WebP with fallbacks)
- [ ] EXIF data removal for privacy
- [ ] Image validation (format, size, content)

#### Storage & Delivery
- [ ] Secure upload to Supabase Storage with proper permissions
- [ ] CDN integration for fast global image delivery
- [ ] Image URL generation with expiration and security
- [ ] Backup and redundancy for image storage
- [ ] Efficient image metadata storage and retrieval

#### Gallery Management
- [ ] Image reordering with drag-and-drop
- [ ] Primary image selection for activity cards
- [ ] Image deletion with confirmation
- [ ] Bulk image operations (select all, delete multiple)
- [ ] Image metadata editing (alt text, captions)

#### Error Handling & Recovery
- [ ] Network error handling with automatic retry
- [ ] Partial upload recovery and resumption
- [ ] Clear error messages with resolution guidance
- [ ] Offline image queuing with sync when online
- [ ] Storage quota management and warnings

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Backend Image Processing** (90 minutes)
   - Set up Supabase Storage with proper bucket configuration
   - Implement image processing and optimization pipeline
   - Create image metadata management APIs
   - Add image validation and security measures

2. **Frontend Upload Components** (120 minutes)
   - Build image selection and upload components
   - Implement upload progress tracking and cancellation
   - Create image gallery management interface
   - Add image preview and editing capabilities

3. **Integration & Optimization** (60 minutes)
   - Integrate image components with activity creation/editing
   - Optimize image loading and caching strategies
   - Add offline support and error recovery
   - Implement performance monitoring and analytics

### Deliverables
- [ ] Image upload and selection components
- [ ] Image processing and optimization pipeline
- [ ] Supabase Storage integration with CDN
- [ ] Image gallery management interface
- [ ] Image metadata management APIs
- [ ] Upload progress tracking and error handling
- [ ] Image validation and content moderation
- [ ] Performance optimization and caching
- [ ] Comprehensive testing and error scenarios

### Technical Specifications

#### Database Schema
```sql
-- Activity images table
CREATE TABLE activity_images (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  storage_path VARCHAR(255) NOT NULL,
  original_filename VARCHAR(255),
  file_size INTEGER NOT NULL,
  mime_type VARCHAR(50) NOT NULL,
  width INTEGER,
  height INTEGER,
  is_primary BOOLEAN DEFAULT false,
  alt_text TEXT,
  caption TEXT,
  display_order INTEGER DEFAULT 0,
  upload_status VARCHAR(20) DEFAULT 'pending' CHECK (upload_status IN ('pending', 'processing', 'completed', 'failed')),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Image processing jobs table
CREATE TABLE image_processing_jobs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  image_id UUID REFERENCES activity_images(id) ON DELETE CASCADE,
  job_type VARCHAR(50) NOT NULL, -- 'resize', 'compress', 'optimize'
  status VARCHAR(20) DEFAULT 'pending',
  input_path VARCHAR(255),
  output_path VARCHAR(255),
  parameters JSONB,
  error_message TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  completed_at TIMESTAMP WITH TIME ZONE
);
```

#### Image Processing Pipeline
```typescript
interface ImageProcessingConfig {
  sizes: {
    thumbnail: { width: 150, height: 150 };
    card: { width: 400, height: 300 };
    full: { width: 1200, height: 900 };
  };
  quality: {
    thumbnail: 80;
    card: 85;
    full: 90;
  };
  formats: ['webp', 'jpeg'];
}

class ImageProcessor {
  async processImage(
    file: File,
    config: ImageProcessingConfig
  ): Promise<ProcessedImage[]> {
    const results: ProcessedImage[] = [];
    
    for (const [sizeName, dimensions] of Object.entries(config.sizes)) {
      // Resize image
      const resized = await this.resizeImage(file, dimensions);
      
      // Optimize for different formats
      for (const format of config.formats) {
        const optimized = await this.optimizeImage(
          resized,
          format,
          config.quality[sizeName]
        );
        
        results.push({
          size: sizeName,
          format,
          blob: optimized,
          metadata: await this.extractMetadata(optimized),
        });
      }
    }
    
    return results;
  }
  
  private async resizeImage(file: File, dimensions: Dimensions): Promise<Blob> {
    // Image resizing logic using canvas or image processing library
  }
  
  private async optimizeImage(
    blob: Blob,
    format: string,
    quality: number
  ): Promise<Blob> {
    // Image optimization and compression
  }
  
  private async extractMetadata(blob: Blob): Promise<ImageMetadata> {
    // Extract image metadata (dimensions, file size, etc.)
  }
}
```

#### Upload Components
```typescript
interface ImageUploadProps {
  activityId?: string;
  maxImages?: number;
  onUploadComplete: (images: ActivityImage[]) => void;
  onUploadProgress: (progress: UploadProgress) => void;
  onError: (error: UploadError) => void;
}

const ImageUpload: React.FC<ImageUploadProps> = ({
  activityId,
  maxImages = 10,
  onUploadComplete,
  onUploadProgress,
  onError,
}) => {
  const [selectedImages, setSelectedImages] = useState<File[]>([]);
  const [uploadProgress, setUploadProgress] = useState<Map<string, number>>(new Map());
  const [isUploading, setIsUploading] = useState(false);
  
  const handleImageSelection = async () => {
    try {
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsMultipleSelection: true,
        quality: 1,
        allowsEditing: false,
      });
      
      if (!result.canceled) {
        setSelectedImages(result.assets.map(asset => asset.uri));
      }
    } catch (error) {
      onError({ type: 'selection', message: 'Failed to select images' });
    }
  };
  
  const handleUpload = async () => {
    setIsUploading(true);
    
    try {
      const uploadPromises = selectedImages.map(async (image, index) => {
        const processed = await imageProcessor.processImage(image, processingConfig);
        
        return uploadToSupabase(processed, {
          activityId,
          onProgress: (progress) => {
            setUploadProgress(prev => new Map(prev.set(image.uri, progress)));
            onUploadProgress({ imageIndex: index, progress });
          },
        });
      });
      
      const uploadedImages = await Promise.all(uploadPromises);
      onUploadComplete(uploadedImages);
    } catch (error) {
      onError({ type: 'upload', message: error.message });
    } finally {
      setIsUploading(false);
    }
  };
  
  return (
    <View>
      <ImageSelectionGrid
        images={selectedImages}
        onAdd={handleImageSelection}
        onRemove={(index) => {
          setSelectedImages(prev => prev.filter((_, i) => i !== index));
        }}
        maxImages={maxImages}
      />
      
      <UploadProgressBar
        progress={Array.from(uploadProgress.values())}
        isVisible={isUploading}
      />
      
      <Button
        title="Upload Images"
        onPress={handleUpload}
        disabled={selectedImages.length === 0 || isUploading}
      />
    </View>
  );
};
```

#### Supabase Storage Integration
```typescript
class SupabaseImageService {
  private bucket = 'activity-images';
  
  async uploadImage(
    file: ProcessedImage,
    metadata: ImageUploadMetadata
  ): Promise<ActivityImage> {
    const fileName = this.generateFileName(file, metadata);
    const filePath = `${metadata.activityId}/${fileName}`;
    
    // Upload to Supabase Storage
    const { data, error } = await supabase.storage
      .from(this.bucket)
      .upload(filePath, file.blob, {
        contentType: file.metadata.mimeType,
        upsert: false,
      });
    
    if (error) throw new Error(`Upload failed: ${error.message}`);
    
    // Save image metadata to database
    const imageRecord = await this.saveImageMetadata({
      activityId: metadata.activityId,
      storagePath: data.path,
      originalFilename: metadata.originalFilename,
      fileSize: file.metadata.size,
      mimeType: file.metadata.mimeType,
      width: file.metadata.width,
      height: file.metadata.height,
    });
    
    return imageRecord;
  }
  
  async getImageUrl(storagePath: string, size: string = 'full'): Promise<string> {
    const { data } = await supabase.storage
      .from(this.bucket)
      .createSignedUrl(storagePath, 3600); // 1 hour expiry
    
    return data?.signedUrl || '';
  }
  
  async deleteImage(imageId: string): Promise<void> {
    // Delete from storage and database
    const image = await this.getImageById(imageId);
    
    await supabase.storage
      .from(this.bucket)
      .remove([image.storage_path]);
    
    await supabase
      .from('activity_images')
      .delete()
      .eq('id', imageId);
  }
  
  private generateFileName(file: ProcessedImage, metadata: ImageUploadMetadata): string {
    const timestamp = Date.now();
    const extension = file.format === 'webp' ? 'webp' : 'jpg';
    return `${file.size}_${timestamp}.${extension}`;
  }
}
```

### Quality Checklist
- [ ] Image processing maintains quality while optimizing size
- [ ] Upload components provide clear progress feedback
- [ ] Error handling covers all failure scenarios
- [ ] Storage integration is secure and performant
- [ ] Gallery management is intuitive and responsive
- [ ] Performance optimized for mobile devices
- [ ] Accessibility features for image management
- [ ] Comprehensive testing including edge cases

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E03 Activity Management  
**Feature**: F01 Activity CRUD Operations  
**Dependencies**: T02 Activity APIs, T03 Creation Components, Supabase Storage  
**Blocks**: Complete Activity Creation Workflow
