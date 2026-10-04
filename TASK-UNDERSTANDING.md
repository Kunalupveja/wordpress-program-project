# Task Understanding Document

## Project: WordPress Programs Module

**Date:** October 4, 2026  
**Author:** [Your Name]  
**AI Tools Used:** Kiro AI (for code generation, documentation, and implementation assistance)

---

## Summary

I am building a comprehensive WordPress Programs module that converts a Figma design into a fully responsive website while implementing a complete custom post type (CPT) system. The module includes:

- A custom "program" post type with "program-type" hierarchical taxonomy (Undergraduate/Postgraduate)
- A custom React-based admin interface for managing program meta fields including a repeater for content sections
- Custom frontend routing for `/programmes`, `/programmes/undergraduate`, `/programmes/postgraduate`, and individual program pages
- A reusable, responsive program card component displaying key program information
- An Interactivity API-powered filter system for client-side filtering without page reloads
- A comprehensive click tracking system that logs all user interactions (links and buttons) to JSON Lines format log files via a REST API endpoint
- Integration of existing HTML/CSS templates from Figma design into WordPress theme structure

---

## Task Breakdown with Time Estimates

### Phase 1: Documentation & Planning (1 hour)
1. **Task Understanding Documentation** - 30 minutes
   - Create this document with all required sections
   - Create Task 1.1 understanding for mobile sticky CTA
   
2. **Git Workflow Setup** - 30 minutes
   - Verify branch structure (main → uat → dev → feature/*)
   - Set up .gitignore properly
   - Document Git workflow process

### Phase 2: Core WordPress Structure (1.5 hours)
3. **Plugin Foundation** - 45 minutes
   - Create plugin boilerplate structure
   - Set up autoloader
   - Add activation/deactivation hooks
   - Initialize package.json for @wordpress/scripts

4. **CPT & Taxonomy Registration** - 45 minutes
   - Register "program" CPT with 'programmes' slug
   - Register "program-type" hierarchical taxonomy
   - Create Undergraduate and Postgraduate terms
   - Test URL structure and permalinks

### Phase 3: Custom Fields & Admin UI (2 hours)
5. **Meta Field Registration** - 30 minutes
   - Register Academic Year (text)
   - Register Duration (text)
   - Register Content Sections (array/repeater)
   - Enable REST API support with `show_in_rest => true`

6. **React Admin Interface** - 1.5 hours
   - Set up @wordpress/scripts build process
   - Build custom metabox UI with React
   - Implement repeater functionality (add/remove/reorder/collapse)
   - Integrate WordPress Media Library
   - Add validation, sanitization, nonces, and capability checks
   - Style with custom CSS (clean, modern UI)

### Phase 4: Frontend Routing & Templates (1.5 hours)
7. **Custom Rewrite Rules** - 30 minutes
   - Add rewrite rules for custom routing structure
   - Ensure no 404s or slug conflicts
   - Flush rewrite rules on plugin activation
   - Test all URL patterns

8. **Template Creation** - 1 hour
   - Create archive-program.php (all programmes)
   - Create taxonomy-program-type.php (filtered by type)
   - Create single-program.php (individual programme detail)
   - Create reusable program card template part
   - Implement responsive grid (3/2/1 columns)
   - Add breadcrumbs and navigation

### Phase 5: Interactivity API Implementation (1 hour)
9. **Filter System** - 1 hour
   - Implement Interactivity API store and directives
   - Create filter bar (All/Undergraduate/Postgraduate)
   - Add client-side filtering without page reload
   - Implement URL updates with history.pushState
   - Add loading and empty states
   - Ensure progressive enhancement (works without JS)

### Phase 6: Click Tracking System (1.5 hours)
10. **Tracking Helper & Frontend** - 45 minutes
    - Create PHP helper function for data-track attribute generation
    - Implement event delegation with vanilla JS
    - Use navigator.sendBeacon() for reliable tracking
    - Track all required elements (cards, filters, breadcrumbs, CTAs)
    - Ensure non-blocking behavior

11. **REST API Endpoint & Logging** - 45 minutes
    - Create POST /wp-json/programs/v1/track endpoint
    - Implement strict payload validation
    - Write to JSON Lines format log files
    - Implement daily log rotation
    - Add file locking (LOCK_EX)
    - Store logs in non-public location
    - Add proper error handling

### Phase 7: Content & Templates Integration (1 hour)
12. **HTML/CSS Template Integration** - 30 minutes
    - Import existing HTML/CSS from other workspace
    - Convert to WordPress theme templates
    - Ensure Figma design fidelity
    - Implement responsive design

13. **Sample Content Creation** - 30 minutes
    - Create 10 sample programs (5 UG, 5 PG)
    - Add featured images
    - Populate all meta fields
    - Add content sections with images

### Phase 8: Testing & Documentation (1.5 hours)
14. **Testing & Refinement** - 45 minutes
    - Test all URLs and routing
    - Verify click tracking logs
    - Test Interactivity API filtering
    - Test admin UI functionality
    - Cross-browser testing
    - Responsive design testing

15. **Documentation & Deliverables** - 45 minutes
    - Write comprehensive README
    - Include setup instructions
    - Document build process
    - Include sample log lines
    - Document Git workflow
    - Create AI prompts document
    - Export database/WXR

### Phase 9: Git Workflow & PR Creation (30 minutes)
16. **Pull Requests** - 30 minutes
    - Commit with meaningful messages
    - Push feature branches
    - Create PRs: feature → dev
    - Merge and create PRs: dev → uat
    - Merge and create PRs: uat → main
    - Write PR descriptions with testing instructions

**Total Estimated Time: 8 hours**

---

## Architecture

### Theme vs. Plugin Separation

**Plugin (programs-module):**
- CPT and taxonomy registration
- Meta field registration
- Admin UI (React metabox)
- Custom rewrite rules
- Click tracking REST API endpoint
- All business logic and data structure

**Theme (project - custom theme):**
- Template files (archive, taxonomy, single)
- Reusable template parts (program card)
- Frontend styles and layout
- Interactivity API implementation
- Click tracking frontend JavaScript
- HTML/CSS from Figma design

**Rationale:**
- Plugin handles all data structure and admin functionality (portable)
- Theme handles all presentation and user-facing templates (design-specific)
- This separation allows the plugin to work with any theme with minimal template additions

### Folder Structure

```
wp-content/
├── plugins/
│   └── programs-module/
│       ├── programs-module.php (main plugin file)
│       ├── includes/
│       │   ├── class-programs-cpt.php (CPT registration)
│       │   ├── class-programs-meta.php (meta field registration)
│       │   ├── class-programs-routing.php (custom rewrites)
│       │   ├── class-programs-tracking.php (REST API & logging)
│       │   └── class-programs-enquiry.php (enquiry form handling)
│       ├── admin/
│       │   ├── class-programs-admin.php (admin UI registration)
│       │   └── css/
│       │       └── admin-styles.css
│       ├── src/
│       │   ├── admin/
│       │   │   ├── index.js (React entry point)
│       │   │   ├── components/
│       │   │   │   ├── MetaBox.js
│       │   │   │   ├── Repeater.js
│       │   │   │   └── RepeaterItem.js
│       │   │   └── styles/
│       │   │       └── admin.scss
│       │   └── interactivity/
│       │       └── view.js (Interactivity API)
│       ├── build/ (compiled assets - included in repo)
│       ├── scripts/ (helper scripts, WP-CLI commands)
│       ├── package.json
│       ├── webpack.config.js
│       └── .gitignore
│
├── themes/
│   └── project/
│       ├── style.css
│       ├── functions.php
│       ├── header.php
│       ├── footer.php
│       ├── front-page.php (homepage from Figma)
│       ├── archive-program.php (all programmes)
│       ├── taxonomy-program-type.php (filtered by type)
│       ├── single-program.php (programme detail)
│       ├── template-parts/
│       │   ├── program-card.php (reusable card)
│       │   ├── programmes-listing.php (listing with filter)
│       │   ├── programmes-breadcrumbs.php
│       │   ├── program-enquiry.php (enquiry form)
│       │   └── sticky-enquire.php (mobile CTA)
│       ├── assets/
│       │   ├── css/
│       │   │   ├── main.css (from Figma/HTML)
│       │   │   ├── programmes.css
│       │   │   └── responsive.css
│       │   ├── js/
│       │   │   ├── tracking.js (click tracking frontend)
│       │   │   └── enquiry-form.js
│       │   └── images/
│       └── inc/
│           └── programs.php (theme program helpers)
│
└── uploads/
    └── programs-tracking/ (log files - non-public)
        ├── .htaccess (deny access)
        ├── index.php (prevent directory listing)
        └── tracking-2026-10-04.log
```

---

## Technical Approach

### (a) React Admin UI

**Technology Stack:**
- @wordpress/scripts for build process (webpack, babel, eslint)
- @wordpress/element for React components
- @wordpress/components for UI primitives
- @wordpress/media-utils for media library integration
- Custom CSS for styling (no default WP admin look)

**Implementation:**
- Register metabox with `add_meta_box()` in PHP
- Enqueue React app with `wp_enqueue_script()` and `wp_enqueue_style()`
- Use `wp_localize_script()` to pass initial data and nonces
- Build interactive repeater with drag-and-drop reordering
- Save via REST API or custom AJAX handler
- Validate and sanitize all input server-side
- Use capability checks (`current_user_can('edit_post', $post_id)`)

**Repeater Features:**
- Add new section button
- Remove section button with confirmation
- Collapse/expand individual sections
- Drag handles for reordering
- Media upload button for each section
- Real-time preview of selected images
- Validation messages

### (b) Click Tracking

**PHP Helper Function:**
```php
function program_track_attr( $event, $label, $section, $component ) {
    $payload = array(
        'type' => 'click',
        'event' => $event, // 'link' or 'button'
        $event => array(
            'label' => sanitize_key( $label ),
        ),
        'content_data' => array(
            'sectionName' => sanitize_text_field( $section ),
            'uiComponent' => sanitize_text_field( $component ),
        ),
    );
    
    return 'data-track="' . esc_attr( wp_json_encode( $payload ) ) . '"';
}
```

**Frontend Implementation:**
- Event delegation on document level (single listener)
- Check for `[data-track]` attribute on click target or ancestors
- JSON.parse with try/catch for error handling
- Use `navigator.sendBeacon()` for reliability (works even on page unload)
- Fallback to `fetch()` with `keepalive: true`
- Never block or delay navigation
- Handle dynamically added elements automatically

**REST API Endpoint:**
- Route: `POST /wp-json/programs/v1/track`
- Validation:
  - Check `type === 'click'`
  - Validate event is 'link' or 'button'
  - Sanitize all string fields
  - Reject oversized payloads
  - Drop unknown keys
- Permission callback: Public with rate limiting explanation
- Add server-side fields: timestamp, page_url
- Write as JSON Lines (one event per line)
- Use `fopen()` with `LOCK_EX` for atomic writes
- Daily log rotation with date in filename
- Store in wp-content/uploads/programs-tracking/ with .htaccess deny

**Tracked Elements:**
- Program card links (label: programme-card-{slug})
- Filter bar items (label: filter-all, filter-undergraduate, filter-postgraduate)
- Breadcrumb links (label: breadcrumb-{term-slug})
- Programme type badge link (label: programme-type-{slug})
- "Back to programmes" link (label: back-to-programmes)
- "Apply now" link (label: apply-now)
- "Enquire now" button (label: enquire-now)

### (c) Interactivity API

**WordPress Interactivity API:**
- Use `@wordpress/interactivity` runtime
- Define store with state and actions
- Use directives: `data-wp-interactive`, `data-wp-on--click`, `data-wp-class`, `data-wp-bind`
- Server-side render with directives in template
- Client-side hydration and state management

**Implementation:**
```javascript
// src/interactivity/view.js
import { store, getContext } from '@wordpress/interactivity';

store('programs/filter', {
    state: {
        selectedType: 'all',
        isLoading: false,
    },
    actions: {
        filterProgrammes: (event) => {
            const context = getContext();
            const type = event.target.dataset.type;
            
            state.selectedType = type;
            state.isLoading = true;
            
            // Update URL
            const url = type === 'all' 
                ? '/programmes' 
                : `/programmes/${type}`;
            history.pushState(null, '', url);
            
            // Filter logic or fetch via REST API
            // ...
            
            state.isLoading = false;
        },
    },
});
```

**Features:**
- No page reload on filter change
- URL updates reflect current filter
- Loading state during transitions
- Empty state when no results
- Progressive enhancement (works without JS via links)
- Accessible (keyboard navigation, ARIA attributes)

---

## Assumptions

1. **WordPress Version:** Assuming WordPress 6.4+ for Interactivity API support
2. **PHP Version:** Assuming PHP 8.0+ for modern syntax
3. **Node.js:** Assuming Node 18+ and npm available for build process
4. **Permalink Structure:** Assuming pretty permalinks are enabled (not default `?p=123`)
5. **Theme Choice:** Using custom classic theme for maximum control over templates
6. **Build Files:** Including `build/` directory in Git as stated in README requirement
7. **Figma Access:** Assuming I have access to inspect Figma design or HTML/CSS templates are provided
8. **Sample Images:** Using placeholder images or stock photos for the 10 sample programs
9. **Log Storage:** Storing logs in `wp-content/uploads/programs-tracking/` as it's writable and can be protected
10. **Rate Limiting:** Not implementing rate limiting on tracking endpoint initially, but documenting why it's public and recommending future implementation
11. **Privacy:** Not logging IP addresses or personal data in tracking, only interaction events
12. **Browser Support:** Supporting modern evergreen browsers (Chrome, Firefox, Safari, Edge) with CSS Grid
13. **Mobile Breakpoints:** Using 768px for tablet, 1024px for desktop
14. **Enquiry Form:** Creating a simple modal form (not full form processor/CRM integration)
15. **Apply Now URL:** Using a placeholder URL or settings field for "Apply Now" link destination

---

## Questions for Clarification

1. **Figma Access:** Do you have a Figma share link, or are the HTML/CSS templates already converted and available in another workspace folder?

2. **Enquiry Form Submission:** Should the enquiry form save submissions to the database, send emails, or just trigger the thank you message? Do we need lead storage?

3. **Apply Now Destination:** Where should the "Apply now" links point to? An external application portal, a WordPress page, or a settings-configurable URL?

4. **Sample Program Content:** Should I use lorem ipsum and placeholder images, or do you have specific program names and content to use?

5. **Hosting Environment:** Is this running on Local by Flywheel or another environment? Any specific server configuration considerations?

6. **Click Tracking Privacy:** Should we add a cookie consent check before tracking, or is this internal analytics exempt from GDPR?

7. **REST API Security:** Besides validation, do you want rate limiting, IP blocking, or other security measures on the tracking endpoint?

8. **Log File Retention:** Should logs auto-delete after 30 days, or keep indefinitely?

9. **WP-CLI Seeder:** Would you like a WP-CLI command to generate the 10 sample programs automatically, or manual creation is fine?

10. **Video Recording:** For the task understanding video, would you prefer screen recording with voiceover, or is this written document sufficient?

**Decision if no response:** I will proceed with assumptions documented above and build a fully functional system. Any uncertainties will be noted in the README.

---

## Risks & Mitigation

### High-Risk Items

1. **Interactivity API Complexity (Risk: Medium, Impact: High)**
   - The WordPress Interactivity API is relatively new (WordPress 6.5+)
   - Documentation may be limited
   - **Mitigation:** Fall back to vanilla JavaScript with manual state management if needed
   - **Cut if needed:** No - this is a core requirement

2. **React Admin UI Build Setup (Risk: Low, Impact: Medium)**
   - @wordpress/scripts can have version conflicts
   - Build process may fail on different environments
   - **Mitigation:** Pin exact versions, include detailed build instructions
   - **Cut if needed:** No - core requirement

3. **Custom Routing Conflicts (Risk: Medium, Impact: High)**
   - WordPress rewrite rules can conflict with existing pages/posts
   - Taxonomy archives and CPT archives may collide
   - **Mitigation:** Test thoroughly, use unique slugs, flush rewrites properly
   - **Cut if needed:** No - core requirement

4. **Click Tracking Log File Permissions (Risk: High, Impact: Medium)**
   - Web server may not have write permissions to log directory
   - File locking may fail on some hosting environments
   - **Mitigation:** Create directory with proper permissions, handle write failures gracefully, document server requirements
   - **Cut if needed:** No - core requirement

### Medium-Risk Items

5. **HTML/CSS Template Integration (Risk: Medium, Impact: Medium)**
   - Existing templates may need significant refactoring for WordPress
   - Responsive design may need adjustments
   - **Mitigation:** Review templates early, allocate buffer time
   - **Cut if needed:** Simplify layouts but maintain mobile responsiveness

6. **Time for Testing (Risk: Medium, Impact: Medium)**
   - 8 hours is tight for this scope
   - Testing may be rushed
   - **Mitigation:** Build with testing in mind, use browser dev tools efficiently
   - **Cut if needed:** Focus on critical path testing (routing, tracking, admin UI)

7. **Git Workflow PR Reviews (Risk: Low, Impact: Low)**
   - Creating PRs and documentation takes time
   - **Mitigation:** Write PR descriptions as I complete each feature
   - **Cut if needed:** Keep PR descriptions concise but complete

### Cut Priority (if time runs short)

**Will NOT cut (core requirements):**
- CPT and taxonomy registration
- Meta fields and admin UI
- Custom routing
- Program card and detail page
- Interactivity API filter
- Click tracking system

**Can simplify (non-essential):**
1. **Bonus features** (transient caching, schema markup, unit tests, WP-CLI) - not required
2. **Complex CSS animations** - use simpler transitions
3. **Multiple feature branches** - use one consolidated feature branch
4. **Extensive sample content** - create minimal 10 programs with basic content
5. **Log rotation automation** - implement daily naming but skip auto-cleanup

**Last resort (only if critical):**
1. Simplify repeater UI (keep add/remove, skip drag reordering)
2. Reduce custom styling on admin UI (keep functional, reduce polish)
3. Skip URL updates in Interactivity API (filter works but URL stays same)

---

## Plan vs Actual (To be completed after implementation)

*This section will be filled after project completion to document what changed from the original plan and why.*

**Planned Completion Time:** 8 hours

**Actual Completion Time:** [To be recorded]

**Major Changes:**
- [To be documented]

**Challenges Encountered:**
- [To be documented]

**Successful Decisions:**
- [To be documented]

**What I Would Do Differently:**
- [To be documented]

---

## AI Tools Disclosure

**Primary Tool:** Kiro AI (AI-powered development environment built on Claude)

**Usage:**
- Code generation for plugin structure, CPT registration, React components
- Template creation and conversion
- JavaScript implementation for Interactivity API and click tracking
- Documentation writing and README generation
- Git workflow assistance
- Debugging and problem-solving
- Code review and optimization suggestions

**Detailed prompts will be provided in a separate `AI-PROMPTS.md` document as required.**

---

*Document created: October 4, 2026*  
*Last updated: October 4, 2026*
