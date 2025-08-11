# Social Membership System

Dette dokument beskriver det komplette membership system der er udviklet til OpenSocial platformen.

## 📋 Oversigt

Social Membership System er et modulært system til håndtering af medlemskaber i OpenSocial. Systemet administrerer medlems-ID'er, medlemsperioder, rolle-tildeling og fornyelse af medlemskaber.

## 🏗️ Modul Struktur

Systemet består af 4 submoduler organiseret under hovedmodulet:

```
social_membership_system/                    (Hovedmodul)
├── modules/
│   ├── social_member_id/                   (MemberID entitet)
│   ├── social_membership/                  (Membership entitet + rolle-logik)
│   └── social_membership_menu/             (Menu integration)
├── src/Controller/UserMembershipController.php
├── social_membership_system.routing.yml
└── social_membership_system.links.menu.yml
```

### Hovedmodul: `social_membership_system`
- **Formål:** Controller og routing for brugergrænseflade
- **Afhængigheder:** `user`, `datetime`
- **Indeholder:** UserMembershipController, routing, menu links

### Submodul: `social_member_id` 
- **Formål:** Administrerer unikke medlems-ID'er
- **Afhængigheder:** `user`, `datetime`, `social_membership_system`
- **Entitet:** `MemberID`

### Submodul: `social_membership`
- **Formål:** Administrerer medlemsperioder og rolle-logik
- **Afhængigheder:** `datetime`, `social_membership_system`, `social_member_id`
- **Entitet:** `Membership`

### Submodul: `social_membership_menu`
- **Formål:** Integrerer "My Membership" link i account dropdown
- **Afhængigheder:** `social_user`, `social_membership_system`
- **Hook:** `hook_social_user_account_header_account_links()`

## 🗄️ Database Struktur

### MemberID Entitet
```sql
member_id:
- id (int, auto)
- uuid (varchar)
- member_id (varchar, 50) - Format: ÅÅÅÅNNN (f.eks. 2025001)
- user_id (int) - Reference til users tabel
- created (timestamp)
```

**Relationer:**
- `user_id` → `users.uid` (Many-to-One)

### Membership Entitet  
```sql
membership:
- id (int, auto)
- uuid (varchar)
- member_id_ref (int) - Reference til member_id tabel
- start_date (date)
- end_date (date) 
- created (timestamp)
```

**Relationer:**
- `member_id_ref` → `member_id.id` (Many-to-One)

## 🔢 Medlemsnummer System

### Format: `ÅÅÅÅNNN`
- **ÅÅÅÅ:** 4-cifret årstal (f.eks. 2025)
- **NNN:** 3-cifret løbenummer med zero-padding (001, 002, etc.)

### Automatisk Generering
- **Første medlem af året:** 2025001
- **Næste medlem:** 2025002, 2025003, etc.
- **Nyt år:** Starter forfra med 2026001

### Implementation
```php
// I MemberID entity preSave() + form_alter hook
protected function generateNextMemberID(EntityStorageInterface $storage) {
  $current_year = date('Y');
  // Find højeste medlem-ID for indeværende år
  // Increment løbenummer + zero-pad til 3 cifre
}
```

## ⚙️ Rolle-Administration

### Automatisk Rolle-Tildeling

**"Verified user" rolle tildeles automatisk når:**
- Nyt aktivt medlemskab oprettes
- Eksisterende medlemskab bliver aktivt

**Rolle fjernes automatisk når:**
- Medlemskab bliver inaktiv (ved redigering)
- Medlemskab slettes
- Cron job opdager udløbne medlemskaber

### Implementation Hooks
```php
// I social_membership.module
function social_membership_entity_insert(EntityInterface $entity)
function social_membership_entity_update(EntityInterface $entity) 
function social_membership_entity_delete(EntityInterface $entity)
function social_membership_cron()
```

### Cron Job Cleanup
- **Kører:** Ved hver Drupal cron kørsel
- **Funktion:** Scanner alle "Verified" brugere
- **Action:** Fjerner rolle hvis ingen aktive medlemskaber
- **Logging:** Logger alle rolle-ændringer

## 🎯 Brugergrænseflade

### Medlemskabs-Oversigt (`/user/membership`)

**Viser:**
- Brugerens medlem-ID og oprettelsesdato
- Tabel med alle medlemsperioder (aktive, udløbne, fremtidige)
- Status-badges med farver
- Varighed i dage for hvert medlemskab

**Conditional Content:**
- **Aktivt medlemskab:** Grøn success-besked med udløbsdato
- **Ingen aktivt medlemskab:** Gul warning-besked
- **Fornyelsesknap:** Vises kun hvis udløbne medlemskaber eksisterer

### Fornyelse Funktionalitet

**"Forny medlemskab" knap:**
- **Vises kun:** For brugere med udløbne medlemskaber
- **Opretter:** Nyt medlemskab fra i dag til 31. december samme år
- **Validering:** Forhindrer fornyelse hvis allerede aktivt medlemskab

**URL:** `/user/membership/renew`

### Menu Integration

**"My Membership" link i account dropdown:**
- **Placering:** Profile dropdown menu (socialblue_accountheaderblock)
- **Hook:** `hook_social_user_account_header_account_links()`
- **Kun for:** Logged-in brugere

## 👨‍💼 Admin Interface

### MemberID Administration (`/admin/people/member-ids`)
- **Liste:** Medlem-ID, Bruger, Oprettelsesdato
- **CRUD:** Opret, rediger, slet, vis medlem-ID'er
- **Auto-udfyldning:** Medlem-ID feltet udfyldes automatisk

### Membership Administration (`/admin/people/memberships`)  
- **Liste:** Medlem-ID, Bruger, Periode, Status, Oprettelsesdato
- **CRUD:** Opret, rediger, slet, vis medlemskaber
- **Status-visning:** Active (grøn), Expired (rød), Future (blå)

### Permissions
- `administer member ids` - Full adgang til medlem-ID administration
- `administer memberships` - Full adgang til medlemskab administration
- `view member ids` / `view memberships` - Read-only adgang

## 🔧 API & Helper Functions

### MemberID Entity Metoder
```php
// Getters/Setters
public function getMemberID(): string
public function setMemberID(string $member_id)
public function getUser(): ?UserInterface
public function setUser(UserInterface $user)

// Label for entity references
public function label(): string
```

### Membership Entity Metoder
```php
// Getters/Setters  
public function getStartDate(): string
public function getEndDate(): string
public function getMemberIDEntity(): ?MemberID

// Status helpers
public function isActive(): bool
public function isExpired(): bool
```

### Helper Functions
```php
// I social_membership.module
_social_membership_user_has_active_membership($user_id): bool
_social_membership_grant_verified_role(Membership $membership): void
_social_membership_check_and_revoke_verified_role(Membership $membership): void
_social_membership_cleanup_expired_roles(): void
```

## 📊 Data Flow

### Medlemskab Oprettelse
1. **Admin opretter MemberID** → Auto-genereret nummer (2025001)
2. **Admin/bruger opretter Membership** → Kobles til MemberID  
3. **Entity insert hook** → Tjekker om aktivt → Tildeler "Verified" rolle
4. **Bruger får adgang** til community features

### Medlemskab Udløb
1. **Membership bliver inaktiv** (dato passerer eller manuel redigering)
2. **Entity update/cron hook** → Tjekker andre aktive medlemskaber
3. **Ingen aktive fundet** → Fjerner "Verified" rolle
4. **Bruger mister adgang** til community features

### Medlemskab Fornyelse
1. **Bruger klikker "Forny medlemskab"** 
2. **Controller validerer** → Ingen aktive medlemskaber
3. **Opretter nyt membership** → I dag til 31. december
4. **Entity insert hook** → Tildeler "Verified" rolle automatisk

## 🚀 Installation & Opsætning

### Installation
1. Placer moduler i `modules/custom/social_membership_system/`
2. Enable hovedmodul: `social_membership_system`
3. Enable ønskede submoduler:
   - `social_member_id` (påkrævet for medlem-ID'er)
   - `social_membership` (påkrævet for medlemskaber)
   - `social_membership_menu` (valgfri, for menu integration)

### Konfiguration
- **Permissions:** Tildel `administer member ids` og `administer memberships` til relevante roller
- **Menu:** "My Membership" link vises automatisk i account dropdown
- **Cron:** Kører automatisk, ingen ekstra opsætning påkrævet

## 🔍 Fejlfinding

### Almindelige Problemer

**Medlem-ID genereres ikke:**
- Tjek at `social_member_id` modul er enabled
- Clear cache efter kode-ændringer
- Kontroller database permissions

**Rolle tildeles ikke:**
- Tjek at `social_membership` modul er enabled  
- Kontroller at "verified" rolle eksisterer
- Se logs under `/admin/reports/dblog`

**Menu link vises ikke:**
- Tjek at `social_membership_menu` modul er enabled
- Kontroller at bruger er logged ind
- Clear cache efter modul enable

### Logging
Alle vigtige events logges under type:
- `social_membership_system` - Generelle events
- `social_membership` - Rolle-ændringer og cron events  
- `social_member_id` - Medlem-ID events

### Database Vedligeholdelse
- **Cron cleanup:** Kører automatisk, ingen manuel intervention
- **Orphaned records:** MemberID kan eksistere uden Memberships (valid)
- **Data integrity:** Foreign keys sikrer konsistens

## 🚀 Fremtidige Udvidelser

### Planlagte Features
- **Betalingsintegration:** Stripe/PayPal integration for automatisk fornyelse
- **E-mail notifikationer:** Advarsler om udløb, bekræftelser på fornyelse  
- **Rapporter:** Medlemsstatistikker, udløbsrapporter
- **Bulk operations:** Mass import/export af medlemmer

### Udvidelses-punkter
- **Hook system:** Modulet understøtter hooks for custom logik
- **Entity API:** Standard Drupal entities kan udvides med custom fields
- **Controller extension:** Nye routes og controllers kan tilføjes
- **Theme integration:** Templates kan overrides for custom styling

## 📚 Teknisk Reference

### Dependencies
- **Drupal:** ^8 || ^9 || ^10
- **OpenSocial:** social_user modul (for menu integration)
- **Core modules:** user, datetime, entity, field

### Performance
- **Database queries:** Optimeret med proper indexing
- **Caching:** Bruger Drupal's standard entity cache
- **Memory usage:** Minimal - kun loader nødvendige entities

### Security
- **Access control:** Drupal's standard permission system
- **Input validation:** Entity constraints og form validation
- **SQL injection:** Drupal's Entity API forhindrer injection
- **CSRF protection:** Built-in i routing systemet

---

*Dokumentation genereret: Januar 2025*  
*Version: 1.0*  
*Kompatibel med: OpenSocial 12.4.13, Drupal 10*