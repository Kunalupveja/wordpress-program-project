# Task 1.1: Mobile Sticky Enquire CTA - Understanding Document

## Task Reference
**Reference Site:** https://s48378.p938.sites.pressdns.com/  
**Document Type:** Understanding Only (No Implementation Required)

---

## User Story

**As a** prospective student on mobile  
**I want** a persistent Enquire CTA at the bottom of the page  
**So that** I can start an enquiry at any point without scrolling back to a specific CTA

---

## Detailed Description

Add a sticky "Enquire Now" call-to-action button that remains anchored to the bottom of the mobile viewport on all University of London (UoL) programme registration pages and landing pages. The button must be visible as users scroll through the page content. Clicking the sticky CTA should open the Enquire Now popup form (as referenced in the popup ticket).

---

## My Understanding

### Scope
This feature is **mobile-only** and targets specific page types:
1. Programme registration pages (individual degree programmes)
2. Landing pages (general registration entry points)

### Technical Requirements

#### 1. **Visibility & Positioning**
- **CSS Position:** `position: fixed; bottom: 0;` with appropriate z-index
- **Viewport:** Only visible on mobile devices (typical breakpoint: max-width: 768px or 1024px)
- **Scroll Behavior:** Remains visible and anchored as user scrolls up or down
- **Visual Hierarchy:** Must have sufficient z-index to appear above page content but not interfere with other fixed elements

#### 2. **Target Pages (10 URLs)**

**Programme Registration Pages (8):**
1. Computer Science BSc
2. Business Management BA
3. Biomedical Sciences BSc
4. Computer Science with Software Development BSc
5. Accounting and Finance BSc
6. Game Design BSc
7. Accounting and Finance MSc
8. Computer Science MSc

**Landing Pages (2):**
9. `/register/ug-1/` (Undergraduate landing page)
10. `/register/pg-1/` (Postgraduate landing page)

#### 3. **Interaction Behavior**
- **Click Action:** Opens the Enquire Now popup form
- **Popup Form:** Referenced from another ticket (likely a modal/overlay component)
- **No Page Reload:** Should use JavaScript to trigger modal without navigation
- **Accessibility:** Must be keyboard accessible and have proper ARIA attributes

#### 4. **UX Considerations**
- **Non-Intrusive:** Should not overlap or obscure critical page content
- **Footer Handling:** Should not cover or interfere with footer elements
- **Safe Area:** Account for mobile browser UI chrome (address bar, navigation)
- **Touch Target:** Minimum 44x44px touch target for accessibility

---

## Technical Approach

### Implementation Strategy

#### **Option 1: Template-Based (Recommended)**
- Create a reusable template part: `template-parts/sticky-enquire.php`
- Conditionally include in footer.php or via `wp_footer` hook
- Use PHP conditional logic to detect mobile (user agent or CSS media query approach)
- Add CSS class to body for page types that need the sticky CTA

```php
// In functions.php or plugin
function add_sticky_enquire_body_class( $classes ) {
    if ( is_singular( 'program' ) || is_page( array( 'register/ug-1', 'register/pg-1' ) ) ) {
        $classes[] = 'has-sticky-enquire';
    }
    return $classes;
}
add_filter( 'body_class', 'add_sticky_enquire_body_class' );

// In footer.php
if ( in_array( 'has-sticky-enquire', get_body_class() ) ) {
    get_template_part( 'template-parts/sticky-enquire' );
}
```

#### **Option 2: JavaScript Injection**
- Enqueue script only on target pages
- Dynamically create and inject sticky CTA element
- More flexible for A/B testing but slightly slower

#### **Option 3: Shared Component Update**
- If site uses a component system (Gutenberg blocks, ACF blocks), update the relevant component
- Ensures changes propagate automatically to all affected pages

### CSS Implementation

```css
/* Mobile Sticky Enquire CTA */
.sticky-enquire-cta {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    padding: 12px 16px;
    background: #003C71; /* UoL brand color */
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.15);
    display: none; /* Hidden by default */
    animation: slideUp 0.3s ease-out;
}

/* Show only on mobile for specific pages */
@media (max-width: 768px) {
    .has-sticky-enquire .sticky-enquire-cta {
        display: block;
    }
}

.sticky-enquire-cta__button {
    width: 100%;
    padding: 14px 24px;
    background: #FDB913; /* CTA color */
    color: #000;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    min-height: 44px; /* Accessible touch target */
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}
```

### JavaScript Implementation

```javascript
// Attach click handler for sticky CTA
document.addEventListener('DOMContentLoaded', function() {
    const stickyCTA = document.querySelector('.sticky-enquire-cta__button');
    
    if (stickyCTA) {
        stickyCTA.addEventListener('click', function(e) {
            e.preventDefault();
            // Trigger the existing Enquire popup
            // Assuming there's a global function or event
            if (typeof openEnquirePopup === 'function') {
                openEnquirePopup();
            } else {
                // Alternative: trigger custom event
                document.dispatchEvent(new CustomEvent('open-enquire-form'));
            }
        });
    }
});
```

### Accessibility Requirements

```html
<div class="sticky-enquire-cta" role="complementary" aria-label="Quick enquiry access">
    <button 
        class="sticky-enquire-cta__button" 
        type="button"
        aria-label="Open enquiry form"
        data-track='{"type":"click","event":"button","button":{"label":"sticky-mobile-enquire"},"content_data":{"sectionName":"sticky-cta","uiComponent":"componentButton"}}'
    >
        Enquire Now
    </button>
</div>
```

---

## Acceptance Criteria Validation

### ✅ Criterion 1: Sticky Visibility
**Given** a user is on mobile viewing a programme registration or landing page  
**Then** a sticky Enquire Now CTA is visible at the bottom of the viewport as they scroll

**Validation:**
- Test on iOS Safari (iPhone)
- Test on Android Chrome
- Scroll up and down - button remains fixed at bottom
- Check at breakpoints: 375px, 414px, 768px

### ✅ Criterion 2: Opens Popup
**Given** the sticky CTA is clicked  
**Then** the Enquire Now popup form opens

**Validation:**
- Click button on mobile
- Verify popup/modal appears
- Check that popup is the same as inline "Enquire" CTA
- Verify tracking event fires

### ✅ Criterion 3: No Content Overlap
**Given** the sticky CTA renders  
**Then** it does not overlap or obscure critical page content or the footer

**Validation:**
- Scroll to footer - ensure footer content is not obscured
- Check that page content has sufficient padding-bottom to avoid being hidden
- Test on pages with sticky headers to ensure no double-stacking issues
- Verify z-index stacking context

### ✅ Criterion 4: Shared Component
**Given** the fix is applied to a shared component/template  
**Then** it takes effect across all affected pages

**Validation:**
- Test all 10 URLs listed
- Verify consistent behavior across all pages
- Check that desktop views do NOT show the sticky CTA
- Ensure no errors on other pages without the feature

---

## Affected URLs - Testing Checklist

### Programme Registration Pages (8)
- [ ] Computer Science BSc - `/programmes/computer-science-bsc/` or similar
- [ ] Business Management BA - `/programmes/business-management-ba/`
- [ ] Biomedical Sciences BSc - `/programmes/biomedical-sciences-bsc/`
- [ ] Computer Science with Software Development BSc
- [ ] Accounting and Finance BSc
- [ ] Game Design BSc
- [ ] Accounting and Finance MSc
- [ ] Computer Science MSc

### Landing Pages (2)
- [ ] `/register/ug-1/` (Undergraduate)
- [ ] `/register/pg-1/` (Postgraduate)

---

## Dependencies & Integrations

### Required Components
1. **Enquire Now Popup** - Must already exist (from popup ticket)
   - Need JavaScript function or event to trigger popup
   - Need to integrate with existing form handler

2. **Mobile Detection** - CSS media query or PHP user agent check
   - Recommend CSS-only approach for performance

3. **Click Tracking** - Should integrate with existing analytics
   - Add `data-track` attribute with proper payload
   - Use existing tracking system from main project

### Potential Conflicts
1. **Sticky Footer** - If site has sticky footer, may need to adjust positioning
2. **Cookie Banner** - May stack with GDPR cookie consent banner
3. **Live Chat Widget** - May conflict with other fixed bottom elements
4. **Mobile Navigation** - Check z-index hierarchy with mobile menu

---

## Implementation Estimate

### Time Breakdown
1. **Template Creation** - 20 minutes
   - Create `sticky-enquire.php` template part
   - Add conditional logic to footer.php

2. **CSS Styling** - 15 minutes
   - Write mobile-specific styles
   - Add animations and transitions
   - Test responsive behavior

3. **JavaScript Integration** - 15 minutes
   - Connect to existing popup trigger
   - Add click tracking
   - Test event handling

4. **Testing** - 30 minutes
   - Test on 10 URLs
   - Cross-browser testing (iOS, Android)
   - Accessibility check (keyboard navigation, screen reader)
   - Check footer overlap scenarios

5. **QA & Refinement** - 20 minutes
   - Fix any issues found
   - Adjust z-index if needed
   - Optimize animations

**Total Estimated Time:** 1.5 hours (90 minutes)

---

## Risks & Considerations

### Technical Risks
1. **Popup Integration** (Medium Risk)
   - If popup doesn't exist yet, may need to wait or build stub
   - Different popup implementations may need different trigger methods

2. **Z-Index Conflicts** (Low Risk)
   - May conflict with other fixed elements
   - Mitigation: Audit existing fixed elements, establish z-index scale

3. **Performance** (Low Risk)
   - Fixed positioning is generally performant
   - Animation should use transform for GPU acceleration

### UX Risks
1. **Intrusiveness** (Medium Risk)
   - Fixed elements can be annoying if too large
   - Mitigation: Keep button compact, use subtle animation

2. **Accidental Clicks** (Low Risk)
   - User may accidentally tap while scrolling
   - Mitigation: Ensure button is clearly visible and intentional

3. **iOS Safe Area** (Medium Risk)
   - iPhones with home indicator may have button partially obscured
   - Mitigation: Use `padding-bottom: env(safe-area-inset-bottom);`

---

## Recommendations

### Implementation Priority
**Priority: High**
- Improves mobile conversion rates
- Reduces friction in enquiry process
- Quick win (1.5 hours to implement)

### Alternative Approaches
1. **Slide-in from Side** - Button slides in from right side instead of bottom
2. **Expanding FAB** - Small floating action button that expands on tap
3. **Scroll-triggered** - Only appears after user scrolls past first CTA
4. **Smart Hiding** - Hides when user scrolls down, shows when scrolling up

### Future Enhancements
1. **A/B Testing** - Test different copy ("Enquire", "Apply", "Get Info")
2. **Exit Intent** - Make more prominent when user about to leave
3. **Personalization** - Different CTA for UG vs PG programmes
4. **Analytics** - Track impression and click-through rates

---

## Testing Plan

### Manual Testing
1. **Device Testing**
   - iPhone 12/13/14 (Safari)
   - Samsung Galaxy (Chrome)
   - iPad in portrait mode (edge case)

2. **Functionality Testing**
   - Click opens popup ✓
   - Keyboard accessible (Tab navigation) ✓
   - Screen reader announces correctly ✓
   - Works without JavaScript (graceful degradation) ✓

3. **Visual Testing**
   - No content overlap ✓
   - Smooth animation ✓
   - Proper spacing from edges ✓
   - Safe area padding (iPhone bottom bar) ✓

### Automated Testing (Optional)
- Cypress test for popup trigger
- Visual regression test (Percy/Chromatic)
- Lighthouse accessibility audit

---

## Questions for Stakeholders

1. **CTA Copy:** "Enquire Now" or alternative text? (e.g., "Get Info", "Ask a Question")
2. **Colors:** Should match brand guidelines - confirm colors from design system
3. **Animation:** Slide up on page load, or appear only after scroll?
4. **Desktop:** Confirm it should NOT appear on desktop/tablet
5. **Tablet Breakpoint:** Where does "mobile" end? 768px? 1024px?
6. **Popup Dependency:** Is the Enquire popup already built and tested?

---

## Summary

This is a **straightforward mobile UX enhancement** that adds a persistent, accessible sticky CTA to programme pages. The implementation is low-risk and high-value, requiring only:

1. A reusable template part with conditional rendering
2. Mobile-specific CSS with fixed positioning
3. JavaScript to trigger the existing enquire popup
4. Click tracking integration

The feature will improve mobile conversion rates by reducing the friction required to start an enquiry, especially on long programme detail pages. Implementation should take approximately 1.5 hours including testing across all 10 target URLs.

**Recommendation: Implement as part of Phase 7 (Template Integration) in the main project, or as a separate quick-win feature after core functionality is complete.**

---

*Document created: October 4, 2026*  
*This is an understanding document only - no implementation required as part of current submission*
