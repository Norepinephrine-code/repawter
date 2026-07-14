1.1 Project Title
“RePawter: Community-Based Stray Animal Management and Reporting System”
1.2 Project Description
The Philippines face a massive population of stray dogs and cats, leading to safety risks like rabies and vehicular accidents. According to the Department of Health (DOH), the Philippines recorded 426 rabies related deaths in 2024 alone. While animal welfare groups and progressive Barangays want to conduct Trap-Neuter-Return (TNR) programs and low-cost anti-rabies drives, they lack a coordinated system. Residents see strays or animal emergencies but don't know who to report them to, and animal shelters are constantly overwhelmed because they cannot track which households are willing to foster. Most communities also rely solely on manual reporting and record keeping processes, which makes it difficult to quickly respond to incidents or coordinate with the local barangay.

RePawter is a website that centralizes stray animal reporting and foster initiatives to improve animal welfare management in local communities. It aims to improve communication and coordination between Barangays, residents, and animal welfare organizations by creating a centralized system. The website will be a platform where residents can report stray or injured animals, volunteer to foster, and receive updates regarding animal welfare initiatives. The platform aims to simplify community welfare initiatives while also supporting rabies prevention efforts and responsible pet ownership of local community members.
1.3 Target Users
The primary users of RePawter are community residents, barangay officials, and animal welfare organizations.

Community Residents will use the platform to report stray, injured, or abandoned animals within their area. They can also register as volunteers to provide temporary care for rescued animals, receive updates on reported cases, and access information on responsible pet ownership and rabies prevention programs.

Barangay Officials will use the system to monitor reports submitted by residents, coordinate animal welfare initiatives and manage community-based animal control activities. They can also disseminate announcements about vaccination drives, adoption events, and other animal welfare programs.

Animal Welfare Organizations and Shelters will use the platform to receive and manage stray animal reports, coordinate rescue and rehabilitation efforts, monitor foster applications, and track the status of rescued animals. This system will help them prioritize cases and improve communication among residents and local authorities.

Features
Part 1: Client Side

Feature
Capabilities
Registration & Login
Create an account, log in, manage profile (name, barangay, contact). Email based only no social login
Animal Reporting
Submit reports with type, photo, location, and urgency; track status.

Reports must satisfy admin defined verification criteria (required photo, valid barangay, description) before being marked as verified

One photo per report and cannot be edited once under review
Foster Application
Apply to foster, set animal preferences and capacity; view status. Approval is manual and limited to declared capacity
Announcements
View barangay/organization updates on drives and events.
Announcements are displayed on an interactive calendar highlighting important dates (vaccination drives, adoption events, TNR schedules).
An announcement may optionally link to or embed a related Facebook post for additional context.
Read-only for residents — no posting or commenting.
.
Notifications
Receive in-app/email updates on reports, foster applications, and new announcements. No SMS.
Educational Resources
Access information on pet ownership and rabies prevention.
Report History
View your own submitted reports and applications.
Location mapping
Users pin animal sightings on a map to view location-specific reports.
Google Maps integration is an optional stretch enhancement; the baseline implementation allows a user to enter a specific address/landmark for the reported animal's location instead of an interactive pin.


Adoption Gallery
Residents can browse animals currently looking for a permanent home and submit an adoption application.


Part 2: Server Side

Feature
Capabilities
Admin Login & Roles
Secure login with role-based access for barangay officials, animal welfare organizations, and system admins. Roles are predefined and accounts are created manually.
Report Management
View, filter, assign, and update the status of submitted reports.
Reports are checked against a defined verification checklist (e.g. valid photo, matching location, no duplicate report) before an admin can mark a report as “Verified”; unverified reports are flagged for follow-up.
Reports can be archived but not permanently deleted.
Foster Review
Approve or reject applications; view applicant history.
Case Tracking
Track animals from report to resolution.
Announcement Management
Create, edit, publish, and schedule announcements onto the shared calendar.
Optionally attach a link/embed to a related Facebook post when publishing an announcement.
Platform-only; no automated cross-posting to social media.
User Management
Manage, flag, or suspend accounts.
Schedule Management
Create, edit, and manage schedules for drives and animal-welfare operations. Platform-only scheduling, without syncing to external calendar apps.
Analytics and Reports
Generate statistics on report volume, response times, and case outcomes.
Generate exportable/printable reports (e.g. monthly summary reports) for barangay and organization use.
Internal data only; no predictive analytics.


Adoption and Pet Profile Management
Create and publish public pet profiles, review adoption applications, and generate PDF adoption agreements. No automated background checks or legal digital signatures.



User Stories
Community Resident
●        As a Community Resident, I want to create an account using my email and barangay details, so that I can access RePawter's reporting and foster features.
●        As a Community Resident, I want to submit a report about a stray or injured animal with a photo, location, and urgency level, so that the barangay and animal welfare organizations are made aware and can respond quickly.
●        As a Community Resident, I want to see the verification status of my report, so that I know whether it has been reviewed and acted upon.
●        As a Community Resident, I want to apply to foster an animal by setting my preferences and household capacity, so that I can provide temporary care for a rescued animal.
●        As a Community Resident, I want to view announcements on a calendar, so that I can easily see upcoming vaccination drives, adoption events, and TNR schedules.
●        As a Community Resident, I want to open the linked Facebook post from an announcement, so that I can read more details or updates shared on social media.
●        As a Community Resident, I want to receive in-app or email notifications about my reports and applications, so that I stay updated without having to check the site constantly.
●        As a Community Resident, I want to browse educational resources on responsible pet ownership and rabies prevention, so that I can learn how to better care for animals in my community.
●        As a Community Resident, I want to view my own report and application history, so that I can track what I have submitted in the past.
●        As a Community Resident, I want to pin or enter the address of where I saw a stray animal, so that responders know exactly where to find it.
●        As a Community Resident, I want to browse the adoption gallery and submit an adoption application, so that I can give a rescued animal a permanent home.
Barangay Official
●        As a Barangay Official, I want to log in with a role-based account, so that I can access tools relevant to my responsibilities as an official.
●        As a Barangay Official, I want to view and filter incoming animal reports, so that I can prioritize which cases need urgent attention.
●        As a Barangay Official, I want to check a report against the verification criteria, so that I only act on reports that are complete and credible.
●        As a Barangay Official, I want to update the status of a report as it is being handled, so that residents and organizations know its current progress.
●        As a Barangay Official, I want to create and publish announcements to a shared calendar, so that residents are informed of upcoming drives and events in a clear, date-based view.
●        As a Barangay Official, I want to attach a Facebook post link when publishing an announcement, so that residents can access more detailed information already posted on social media.
●        As a Barangay Official, I want to manage schedules for vaccination drives and TNR operations, so that activities are organized and don't conflict with each other.
●        As a Barangay Official, I want to view analytics reports on reports and response times, so that I can evaluate how well my barangay is responding to animal welfare issues.
●        As a Barangay Official, I want to flag or suspend a resident account when necessary, so that I can prevent misuse of the reporting system.
Animal Welfare Organization / Shelter
●        As a Animal Welfare Organization, I want to log in with an organization account, so that I can manage rescue and foster operations relevant to my organization.
●        As a Animal Welfare Organization, I want to receive and manage stray animal reports, so that I can coordinate rescue and rehabilitation efforts efficiently.
●        As a Animal Welfare Organization, I want to review foster applications and applicant history, so that I can approve applicants who are best suited to care for a specific animal.
●        As a Animal Welfare Organization, I want to track a case from initial report to resolution, so that I can monitor the full lifecycle of a rescued animal.
●        As a Animal Welfare Organization, I want to create and publish a public pet profile, so that available animals can be seen and adopted by the community.
●        As a Animal Welfare Organization, I want to generate a PDF adoption agreement, so that I have a documented record once an adoption is approved.
●        As a Animal Welfare Organization, I want to view analytics and generate reports on cases handled, so that I can share outcomes with stakeholders and identify trends.
System Admin
●        As a System Admin, I want to define and update the verification criteria used to review reports, so that only credible, complete reports are acted on by officials and organizations.
●        As a System Admin, I want to manage user accounts across all roles, so that I can maintain the integrity and security of the platform.
●        As a System Admin, I want to view logs of user-management actions, so that I can audit changes made to accounts.
●        As a System Admin, I want to generate platform-wide analytics reports, so that stakeholders can see the overall impact and performance of RePawter.


Limitations
●        Foster and adoption approvals are manual decisions made by organizations/officials — the system does not auto-match applicants to animals.
●        Google Maps integration, SMS notifications, and payment gateways are treated as future enhancements, not baseline requirements.
●        Admin and official accounts are provisioned manually rather than through self-service registration.
●        The system does not integrate with external veterinary record systems for case tracking.

