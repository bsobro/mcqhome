# MCQ Home Theme - Complete Setup Guide

## Overview
The MCQ Home theme is a comprehensive WordPress theme designed for creating and managing Multiple Choice Question (MCQ) quizzes, tests, and mock exams. It includes role-based access control for institutions/teachers and students.

## Features

### User Roles & Capabilities
- **Institution**: Can create unlimited quizzes, manage other teachers' quizzes, and access advanced analytics
- **Teacher**: Can create and manage their own quizzes, view student progress
- **Student**: Can enroll in quizzes, take tests, and view detailed results

### Quiz System
- Create free and paid quizzes
- Set time limits and attempt restrictions
- Add questions with images and explanations
- Automatic grading and detailed result analysis
- Progress saving during quiz attempts
- Performance tracking and analytics

## Initial Setup

### 1. Install WordPress
Ensure WordPress is properly installed and configured.

### 2. Install the Theme
Upload and activate the MCQ Home theme in WordPress.

### 3. Required Pages Setup
Create the following pages and assign the appropriate templates:

#### Required Pages:
1. **Teacher Dashboard** (Page Template: `Teacher Dashboard`)
   - URL: `/teacher-dashboard`
   - Access: Teachers and Institutions

2. **Student Dashboard** (Page Template: `Student Dashboard`)
   - URL: `/student-dashboard`
   - Access: Students only

3. **Institution Dashboard** (Page Template: `Institution Dashboard`)
   - URL: `/institution-dashboard`
   - Access: Institutions only

4. **Quizzes Catalog** (Page with shortcode)
   - Create a new page
   - Add shortcode: `[quiz_catalog]`
   - URL: `/quizzes`

5. **My Quizzes** (Page with shortcode)
   - Create a new page
   - Add shortcode: `[my_quizzes]`
   - URL: `/my-quizzes`

#### Registration Redirect System
The theme now includes automatic registration redirects based on user roles:

- **Students** are redirected to `/student-dashboard`
- **Teachers** are redirected to `/teacher-dashboard`
- **Institutions** are redirected to `/institution-dashboard`

This happens automatically after registration and first login.

#### Dashboard Navigation Features
Each dashboard includes:
- **Responsive navigation menu** with role-specific links
- **Tab-based content organization** for better UX
- **Quick action buttons** for common tasks
- **Mobile-friendly design** with collapsible sidebar
- **Keyboard shortcuts** (Ctrl+1, Ctrl+2, Ctrl+3 for quick navigation)

#### Testing Registration Flow
1. **Register a new student account** - should redirect to student dashboard
2. **Register a new teacher account** - should redirect to teacher dashboard
3. **Register a new institution account** - should redirect to institution dashboard
4. **Test login redirects** - first login after registration should redirect to appropriate dashboard

#### Dashboard Navigation Links
**Student Dashboard Links:**
- Dashboard Overview
- My Quizzes
- Browse Quizzes
- Achievements
- Profile Settings

**Teacher Dashboard Links:**
- Dashboard Overview
- My Quizzes
- Create New Quiz
- Students
- Analytics
- Settings

**Institution Dashboard Links:**
- Overview
- Manage Teachers
- All Quizzes
- All Students
- Analytics
- Settings

### 4. WordPress Settings

#### Permalinks
Go to **Settings > Permalinks** and set to **Post name** for clean URLs.

#### User Registration
Go to **Settings > General** and enable **Anyone can register**.

### 5. Create WordPress Menus

#### Main Navigation Menu
Create a menu with these items:
- Home
- Quizzes
- Teacher Dashboard (for logged-in teachers)
- Student Dashboard (for logged-in students)
- Login/Register (for logged-out users)

#### Footer Menu
- About
- Contact
- Privacy Policy
- Terms of Service

## Quiz Creation Process

### For Teachers/Institutions

#### 1. Access Teacher Dashboard
- Login with teacher/institution account
- Navigate to `/teacher-dashboard`

#### 2. Create a Quiz
- Click "Create New Quiz" in the dashboard
- Fill in the form:
  - **Title**: Quiz name
  - **Description**: Detailed description
  - **Type**: Free, Paid, or Subscription
  - **Price**: If paid (set to 0 for free)
  - **Duration**: Time limit in minutes
  - **Max Attempts**: Number of attempts allowed
  - **Instructions**: Guidelines for students

#### 3. Add Questions
After creating the quiz:
- Go to **Questions > Add New**
- Select the quiz from the dropdown
- Add question details:
  - Question text
  - Options A, B, C, D
  - Correct answer
  - Points (default: 1)
  - Explanation (optional)
  - Featured image (optional)

### For Students

#### 1. Registration
- Go to `/wp-login.php?action=register`
- Select "Student" role during registration
- Complete profile setup

#### 2. Enrolling in Quizzes
- Browse quizzes at `/quizzes`
- Click on a quiz to view details
- Click "Enroll Now" button
- Complete payment if required

#### 3. Taking Quizzes
- Access enrolled quizzes from `/student-dashboard`
- Click "Start Quiz" on any available quiz
- Answer questions within the time limit
- Submit or save progress

## Customization

### Colors and Branding
Edit `style.css` to customize colors, fonts, and layout.

### Email Templates
Customize email notifications by editing the appropriate functions in `functions.php`.

### Payment Integration
The theme includes placeholder functions for payment processing. To integrate real payment:

1. Install a payment plugin (WooCommerce, Stripe, etc.)
2. Modify the payment functions in `quiz-management.php`
3. Update the enrollment process accordingly

## Advanced Features

### Analytics
- **Teacher Dashboard**: View quiz performance, student progress, and revenue
- **Student Dashboard**: Track personal progress and achievements

### Question Types
Currently supports:
- Multiple choice (single correct answer)
- Image-based questions
- Text-based questions

### Performance Tracking
- Automatic grading
- Detailed result analysis
- Time tracking per question
- Attempt history

## Troubleshooting

### Common Issues

#### 1. Quizzes Not Displaying
- Ensure quiz post type is registered
- Check if questions are added to the quiz
- Verify user has appropriate role

#### 2. User Registration Issues
- Check if "Anyone can register" is enabled
- Verify role selection is working
- Check for JavaScript errors

#### 3. Quiz Timer Not Working
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify quiz duration is set

#### 4. Results Not Saving
- Check WordPress AJAX functionality
- Verify user permissions
- Check server error logs

### Debug Mode
Enable WordPress debug mode by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Performance Optimization

### 1. Caching
- Install a caching plugin (WP Super Cache, W3 Total Cache)
- Configure object caching for quiz data

### 2. Images
- Optimize question images
- Use appropriate image sizes
- Enable lazy loading

### 3. Database
- Regularly optimize database tables
- Monitor quiz-related queries
- Use database caching

## Security Best Practices

### 1. User Security
- Strong password requirements
- Regular security updates
- Limit login attempts

### 2. Data Protection
- SSL certificate installation
- Regular backups
- Secure payment processing

### 3. Quiz Security
- Prevent cheating mechanisms
- Time-based access control
- IP-based restrictions

## Support and Updates

### Getting Help
- Check the theme documentation
- Review WordPress support forums
- Contact theme developer for issues

### Regular Maintenance
- Keep WordPress updated
- Update theme and plugins
- Monitor user feedback
- Regular security scans

## Sample Data

### Create Test Users
Create these test accounts for development:

1. **Admin**: Full site access
2. **Institution**: Create and manage quizzes
3. **Teacher**: Create own quizzes
4. **Student**: Take quizzes and view results

### Sample Quiz
Create a test quiz:
- Title: "Sample MCQ Test"
- Description: "A test quiz to verify system functionality"
- Type: Free
- Duration: 30 minutes
- Questions: 5-10 sample questions

## API Endpoints

The theme includes these AJAX endpoints:
- `/wp-admin/admin-ajax.php?action=submit_quiz`
- `/wp-admin/admin-ajax.php?action=save_quiz_progress`
- `/wp-admin/admin-ajax.php?action=get_quiz_results`

## Custom Development

### Adding New Features
- Use child themes for customizations
- Follow WordPress coding standards
- Test thoroughly before deployment

### Extending Quiz Types
- Add new question types via plugins
- Customize grading algorithms
- Integrate with learning management systems

## Success Metrics

Track these metrics for success:
- User registration rates
- Quiz completion rates
- Average quiz scores
- Revenue (for paid quizzes)
- User engagement time

---

For additional support or customization requests, please refer to the theme documentation or contact the development team.