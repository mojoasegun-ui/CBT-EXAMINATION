# ExamCore Public UI: Research and Design Decisions

Research reviewed on 2026-11-02. Product-page order below follows headings and content extracted from the vendors' public homepages at that time; marketing pages can vary by locale, campaign, or later edits. These observations are references, not claims about ExamCore customers or capabilities.

## Research Findings

### Five Assessment and EdTech Homepages

- **TestInvite**: headline and primary CTA; customer logos and usage/reliability metrics; how trusted assessment works; exam-security layers; reporting; question analytics; compliance; authoring/scalability/AI capabilities; platform demo/resources; audience use cases; customer story and ratings; closing CTA and company/legal footer. The page leads with security and defensible results, then expands into evidence, platform capabilities, audiences, and a customer story. [Official homepage](https://www.testinvite.com/)
- **ClassMarker**: exam-maker headline and CTA; longevity/customer marks; product overview/video; demonstration exams; proctoring; how it works (create, setup, give, analyze, certify); feature benefits; security/privacy; question reuse; proctoring; pricing; API/webhooks; use cases; FAQ/resources and footer. It makes the workflow concrete early and lets visitors try a demo before explaining the wider feature set. [Official homepage](https://www.classmarker.com/)
- **ExamSoft**: secure-assessment headline; three benefits (integrity, outcomes/data, educator time); education and industry use cases; summary of secure exams and student outcomes; CTA/footer. The page is intentionally concise and centers its strongest differentiators rather than presenting a long feature catalogue. [Official homepage](https://examsoft.com/)
- **ProProfs Quiz Maker**: product headline and trial/demo CTAs; usage metrics and customer marks; product/AI overview; customer stories; creation steps; quiz and question types; use cases; features; support; FAQ; awards/ratings; closing CTA and footer. It combines proof, a step-by-step workflow, broad capability detail, and visible support. [Official homepage](https://www.proprofs.com/quiz-school/)
- **TalentLMS**: platform headline with demo/signup CTAs; ease-of-start; scaling; intelligence/AI; support; customer story; audiences/solutions; testimonial; course library; awards; signup CTA/footer. It uses simplicity and time-to-value as the first proof, then follows with scale, audience fit, and a customer outcome. [Official homepage](https://www.talentlms.com/)

### UX and Accessibility Principles

- **Visual hierarchy**: Nielsen Norman Group describes hierarchy through contrast, scale, and grouping/proximity. Give the primary promise and action the strongest emphasis; keep subordinate text and repeated cards quieter. Avoid making every item equally prominent. [NN/g: Visual Hierarchy in UX](https://www.nngroup.com/articles/visual-hierarchy-ux-definition/)
- **Whitespace and grouping**: NN/g explains that proximity and whitespace express relationships; increase separation between sections while keeping headings close to their associated content. Refactoring UI recommends beginning with more whitespace, establishing a spacing system, using a clear type scale, and using fewer borders. These are qualitative principles, not a prescribed whitespace percentage. [NN/g](https://www.nngroup.com/articles/visual-hierarchy-ux-definition/) · [Refactoring UI](https://refactoringui.com/)
- **Typographic hierarchy**: NN/g advises using a small number of clearly differentiated sizes and keeping size/weight aligned to importance. Refactoring UI likewise recommends a deliberate type scale, readable line length, and proportional line-height. This page uses the supplied scale and 1.15 heading / 1.6 body leading. [NN/g](https://www.nngroup.com/articles/visual-hierarchy-ux-definition/) · [Refactoring UI](https://refactoringui.com/)
- **Contrast**: WCAG 2.2 AA requires at least 4.5:1 for normal text and 3:1 for large text. These are minimum thresholds, not a guarantee of readability for every person or display. [W3C: Contrast Minimum](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html)
- **Targets**: WCAG 2.2 AA Target Size (Minimum) sets a 24×24 CSS-pixel minimum or qualifying spacing exceptions. Material's accessibility guidance recommends larger targets: 48×48 dp for touch and 8 dp spacing where practical. The implementation uses at least 44px for prominent controls as requested and aims for 48px on mobile navigation controls. [W3C: Target Size Minimum](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html) · [Material accessibility](https://m2.material.io/design/usability/accessibility.html)
- **Mobile-first/responsive behavior**: Material accessibility guidance recommends flexible responsive layouts that avoid truncation. No source establishes one universal breakpoint; the UI follows the requested 768px navigation collapse and tests 375, 768, 1024, and 1440px widths. [Material accessibility](https://m2.material.io/design/usability/accessibility.html)
- **Motion and system status**: NN/g recommends visibility of system status, user control, and minimalist design. Material guidance warns against unmanaged motion and supports accessible control/focus order. The slider has keyboard controls, pauses on hover/focus, stops autoplay for reduced motion, and remains manually navigable. [NN/g heuristics](https://www.nngroup.com/articles/ten-usability-heuristics/) · [Material accessibility](https://m2.material.io/design/usability/accessibility.html)

## Education Hero Pattern

The sampled pages use more than one hero treatment: ClassMarker shows product imagery and demo access; TestInvite presents a product exam preview alongside an outcome-led message; ExamSoft takes a shorter text-led route. For this CBT platform, a split hero is the clearest choice: the copy explains the institution-facing value while a labeled, illustrative exam-interface preview makes the product category immediately legible. Full-bleed photography would consume attention without proving the workflow; an abstract illustration would be less concrete than a product preview. The current SVG is explicitly a replaceable mock, not a real ExamCore screenshot.

## Trust Signals and Claim Policy

The sampled sites use customer logos, verified testimonials/case studies, usage metrics, uptime/service claims, review badges, certifications/compliance statements, and security details. Those signals work only when substantiated. ExamCore currently has no deployed examination workflow, verified institutional customers, uptime history, independent audit, or SOC 2 evidence. The page therefore uses factual platform attributes and clearly marked prelaunch placeholders rather than asserting the requested "500+ institutions", "50K+ exams", "99.9% uptime", SOC 2 status, named testimonials, or customer logos. Replace placeholder partner artwork and quotes only with approved, verified material.

## Proposed Sections and Rationale

1. **Sticky header**: familiar navigation and a clear primary action, with a mobile drawer that supports keyboard focus, Escape, and scroll locking.
2. **Hero**: one outcome-led headline, two clear next steps, factual platform readiness signals, and an illustrative product preview; supports the split pattern above without inventing traction.
3. **Trust strip**: institution categories and replaceable logo placeholders, labeled as placeholders until approved partners exist.
4. **Product showcase**: four navigable interface concepts demonstrate the planned product surface without implying these screens are already operational.
5. **Features**: six roadmap capability cards distinguish proposed capabilities from implemented Phase 1 foundations.
6. **How it works**: three simple workflow steps help institutions recognize the intended assessment lifecycle.
7. **Why ExamCore**: explain actual architecture strengths (integration boundary, cPanel compatibility, security foundations) rather than unverifiable superiority claims.
8. **Testimonials**: clearly labeled quote/rating placeholders avoid fabricated endorsements while reserving the eventual structure.
9. **Pricing**: three tiers invite a pilot/pricing conversation without inventing amounts or checkout behavior.
10. **FAQ**: answer scope, import, mobile, tenant isolation, proctoring, and migration questions truthfully for the current phase.
11. **Final CTA**: direct visitors to the current contact/pilot entry point.
12. **Footer**: product, company, legal, status, and a newsletter form placeholder; the form is not wired to a backend.

## Implementation Decisions and Constraints

- Use Tailwind CSS 3 via local dev dependencies and commit the compiled stylesheet. The project already sends a restrictive `default-src 'self'` Content Security Policy, and backend files are frozen for this task. Google Fonts and icon CDNs would be blocked unless that backend policy changes. Use local/system font stacks headed by the requested Plus Jakarta Sans and Inter names, plus inline SVG icons; this avoids external requests and justifies not adding a blocking icon CDN. If branded web fonts are required, host licensed font files locally in a later approved asset change.
- Keep the requested palette tokens. Use ink on amber CTAs and white on primary-blue surfaces; muted text is checked against its actual background. Visible focus styles remain independent of color alone.
- The live backend has no login, signup, demo-booking, pricing, or newsletter routes. Public buttons therefore navigate to on-page sections or are marked as upcoming; the newsletter form uses `action="#"` and reports that it is not connected. Do not treat these controls as working account or sales workflows.
- All image URLs are rooted at the layout's escaped `$assetRoot`, derived from `$baseUrl`; image placeholders are self-hosted SVG so the page remains complete without binary media or an external image service.

## Sources

- TestInvite: <https://www.testinvite.com/>
- ClassMarker: <https://www.classmarker.com/>
- ExamSoft: <https://examsoft.com/>
- ProProfs Quiz Maker: <https://www.proprofs.com/quiz-school/>
- TalentLMS: <https://www.talentlms.com/>
- Nielsen Norman Group, Visual Hierarchy: <https://www.nngroup.com/articles/visual-hierarchy-ux-definition/>
- Nielsen Norman Group, Usability Heuristics: <https://www.nngroup.com/articles/ten-usability-heuristics/>
- Refactoring UI: <https://refactoringui.com/>
- Material Design accessibility guidance (Material 2 page now links to Material 3): <https://m2.material.io/design/usability/accessibility.html>
- WCAG 2.2 Contrast Minimum: <https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html>
- WCAG 2.2 Target Size Minimum: <https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html>