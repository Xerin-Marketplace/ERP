# XERIN GROUP — ERP + CRM Master System Specification

*Dubai Branch and Tanzania Branch | Prepared 20 November 2026 | For: CEO, Joseph (CTO), Ezra (Developer) | Version 1.0 — draft for management review | CONFIDENTIAL*

## 1. Purpose of this document

This document defines one integrated ERP + CRM platform for Xerin Group Limited, covering both branches (Dubai and Tanzania). It is built from a line-by-line review of three internal files:

- **Xerin\_Operation\_Master\_File-4.xlsx**: the live operations workbook (11 sheets).
- **XERIN\_CRM\_FOR\_DEVELOPER\_TEMPLATE.xlsx**: the blank CRM template (11 sheets).
- **XERIN ERP + CRM System Architecture (PDF, 20 Nov 2026)**: the technical blueprint for Joseph and Ezra.

The document does four things:

1. Records what the current Excel system actually does and what its data actually contains.
2. Lists the gaps that stop management from seeing sales, profit and cash clearly.
3. Defines the target system: modules, data, rules, roles, finance, reporting and controls.
4. Gives a realistic delivery plan and the decisions management must make.

The document is written in English so that it can go directly to the development team. All numbers quoted in section 4 come from the files supplied and are labelled as such.

## 2. Executive summary

**What Xerin already has.** Xerin runs a real cargo chain: goods are received in Dubai, consolidated into boxes of about 23 KG, carried to Tanzania (by passenger carry and by air cargo), received, shelved and invoiced in Tanzania, approved for release and delivered or collected. The Excel master file follows this chain sheet by sheet, and the SOP assigns each sheet to a named person.

**Scale of the business, from the file supplied.**

- About 2,460 receiving lines in Dubai, about 27.9 tonnes.
- 208 consolidation boxes recorded, about 5.2 tonnes.
- About 2,040 receiving lines in Tanzania, about 19.9 tonnes.
- About 2,400 warehouse lines, of which 2,158 are marked Delivered and 166 Collected.
- 82 deliveries recorded in the Delivery Register, with delivery fees of TZS 1,001,000.

**What Excel cannot do.** In the file supplied:

- Customer names and phone numbers are empty in Dubai Receiving and in Goods Received TZ. Customers are therefore not identifiable from the receiving records.
- About 89% of Dubai Receiving lines (2,198 of 2,460) carry the placeholder tracking number "N-A". The same placeholder fills the Box, Trip and Tracking columns in Tanzania. Cargo cannot be traced from Dubai to Tanzania to the customer.
- The freight charge column in Accounting is empty and there is no cost data anywhere. **Revenue, cost and profit cannot be calculated.**
- Payment Approval has no records at all, although the SOP says nothing leaves without approval.
- Three weight totals do not reconcile (Dubai 27.9 t, boxes 5.2 t, Tanzania 19.9 t) and nothing in the file explains the differences.

**What the ERP will do.** One database, one customer record, one tracking number from booking to proof of delivery. Every status change is time-stamped, owned by a named person and visible to the customer. Finance is built in: rate cards, invoices, payments, costs, profit per shipment, per customer, per branch and per route. The CEO sees every number — sales, collections, outstanding money, cost, margin, stock on hand, delays and staff performance — on one dashboard, drillable to the individual shipment.

**Realistic timing.** The architecture PDF targets 30 November 2026. A full ERP of this scope cannot be built and tested safely in 10 days. Section 22 proposes a phased plan: a controlled core release first (tracking, receiving, invoicing, release approval, delivery), then finance depth, CRM, and the CEO analytics suite.

## 3. Method

Every sheet in both workbooks was opened and its columns, formulas, dropdown rules and data were read. Row counts, status distributions, spelling variants and totals were computed from the data. Where a dropdown or formula points to a sheet that is not in the file, this is reported. Prices and rates found in formulas are quoted as found and must be confirmed by management before they are built into the rate card.

## 4. Current-state audit, sheet by sheet

### 4.1 Overview of the master file

| # | Sheet | Purpose (per SOP) | Owner named in SOP | Data found |
| --- | --- | --- | --- | --- |
| 1 | Dashboard | KPIs, monthly and category reporting | Management | Formulas only |
| 2 | Dubai\_Receiving | Every shipment received in Dubai | Ezekiel / Dubai team | \~2,460 rows |
| 3 | Consolidation\_Boxes | One row per 23 KG box | Dubai team | 208 boxes |
| 4 | Box\_Contents | Links each tracking number to a box | Dubai team | \~608 rows |
| 5 | Trip\_Manifest | Passenger trips and the boxes they carry | Dubai team | 6 rows |
| 6 | Goods\_Received\_TZ | Cargo received in Tanzania | Faith | \~2,040 rows |
| 7 | Warehouse\_Management | Shelf, status, storage, release | Hashim | \~2,400 rows |
| 8 | Payment\_Approval | Approved or On Hold before release | Jacqueline | 0 records |
| 9 | Accounting\_Invoicing | Freight, storage and delivery invoices | Sabrina | 499 pre-filled rows |
| 10 | Delivery\_Register | Dispatch and proof of delivery | Driver / delivery team | 82 deliveries |
| 11 | SOP | Rules of use | Management | 12 rules |

The SOP also refers to a **Config\_Lists** sheet (dropdown values, storage rate, free days). Dropdowns and formulas in the file point to it, but **it is not present in the file supplied**. Its values (storage rate, free days, delivery zones) must be recovered from the original file before the rate card is built.

### 4.2 Dashboard

- Shows operations counts (received in Dubai, awaiting consolidation, boxes departed, arrived Tanzania, on shelf, ready, out for delivery, delivered, old stock), collections (freight, storage, delivery, pending, outstanding), a product-category table and a monthly table.
- **Observation.** Several labels and formulas are misaligned by one row. For example, the cell labelled "Delivery Collections" holds the total of paid items, and "TOTAL COLLECTION" holds the pending freight. Several formulas use fixed ranges (rows 2 to 486 or 2 to 500) and will silently ignore new data.
- **Observation.** There is no profit, no cost, no branch comparison, no customer ranking, no ageing and no cash view.
- Product categories used: Electronics, General Cargo, ELI, Online Parcel, Spare Parts, TV, Cosmetics, Others, Mix Items.

### 4.3 Dubai\_Receiving

- Columns: Date Received, Tracking No, Customer Name, Phone, Supplier Name, Supplier Contact, Item Description, Item Type, KG, Pieces, Received Location, Received By, Status, Remarks.
- Supplier dropdown: Amazon, Noon, Shein, 6th Street, Brands for Less, Temu, Aramex, Zara, TOPTEX, Papa and Mama, H&M, Namshi, Next, Other suppliers.
- Location dropdown: Dubai Office or Dubai Warehouse. All 2,460 rows say Dubai Office.
- **Observation.** In the file supplied, Date Received, Customer Name, Phone and Supplier Name are empty on every row. Item Type is filled on only 68 rows, Received By on 237 (Faisal 172, Amina 65) and Status on 2.
- **Observation.** 2,198 rows have tracking "N-A". Total weight is about 27,931 KG.

### 4.4 Consolidation\_Boxes

- Columns: Box Number, Date Packed, Total KG, Total Customers, Packed By, Current Location, Status, Assigned Trip No, Remarks.
- Statuses found: Departed Dubai 200, Closed 7, Assigned to Passenger 1.
- **Observation.** Box numbers use three prefixes (HSR 190, YALLA 11, XRN 7) and **163 box numbers are duplicated** (for example HSR 1 and HSR 3 appear more than once). The Trip Manifest uses a different format (XRN001), so boxes cannot be matched reliably between sheets.
- **Observation.** The SOP says every box is 23 KG; the average recorded box is 25.1 KG and total weight is about 5,215 KG. Packed By has spelling variants (Conrad, conrad, Amina, Feisal).
- Assigned Trip No is filled on 1 of 208 boxes.

### 4.5 Box\_Contents

- Columns: Box Number, Assigned Date, Tracking No, Customer Name, Phone, Item Type, KG, Pieces, Status, Trip No, Remarks.
- **Observation.** Item, KG and status are filled (about 600 rows, 3,319 KG) but Tracking No is filled on only 29 rows and Box Number on 150. The link between customer cargo and box, which is the foundation of traceability, is therefore mostly missing.

### 4.6 Trip\_Manifest

- Columns: Trip No, Passenger Name, Airline, Flight No, Flight Date, Box Number, Box KG, Departure Date, Arrival Date, Status, Remarks.
- One trip recorded: TRIP-001, passenger Fahad, Emirates, 15 June 2026, six boxes (XRN001 to XRN006, 19.0 to 26.8 KG), status Arrived. The flight number is a placeholder (FZXXX).
- **Observation.** The SOP rule "never rely on sequential box numbers" is correct, but 208 boxes are marked Departed Dubai with only one trip in the manifest. Departures cannot be proven from the file.

### 4.7 Goods\_Received\_TZ

- Columns include Tracking No, Received Date TZ, Customer Name, Phone, Box Number, Trip No, Item Type, KG, Pieces, Received By, Receiving Status, Del Number, Remarks, Storage Charge.
- Storage formula as found: if status is Invoiced, then (today minus received date minus 4) days, floored at zero, multiplied by KG and 500. This **conflicts with the SOP** (first 3 days free, charge from day 4) and uses a fixed 500 per KG per day instead of the Config\_Lists values.
- **Observation.** About 2,040 rows and about 19,909 KG. Customer Name, Phone and Del Number are empty. Box and Trip are "N-A" on 1,953 rows. Receiving Status is filled on only 66 rows.
- **Observation.** Item Type is free text with many spellings (cloths, clothes, cosmatic, cosmetics, spare, spare part, spare parts, suppliments) so category reporting is unreliable.
- **Observation.** "Received By" has about 14 variants for four or five people (for example Hashim Issa written five ways, "wahida/abdul", "GM", "Madam Jackline").
- Dates are mixed (text such as "1st Jan 2026", "24.06.2026", true dates), so month reports can misplace shipments.

### 4.8 Warehouse\_Management

- Columns: Tracking No, Customer Name (looked up from Goods\_Received\_TZ), Phone (looked up), Received Date TZ, Item Type, KG, Pieces, Shelf No, Shelf Position, Delivered (status), Customer Option, Ready Date, Released Date, Days in Center, Chargeable Days, Storage Charge, Delivery/Transfer Fee, Total Extra Charges, Payment Status, Handled By, Remarks.
- Customer options and fees as found in formulas: Collect at Tazara — free; Collect at Uhuru Heights — TZS 5,000; Home/Office Delivery — TZS 10,000. Storage = chargeable days × a rate held in Config\_Lists.
- Status counts: Delivered 2,158, Collected 166, On Shelf 75, Ready for Collection 1, Transfer to Uhuru Heights 1. Payment Status is "Paid" on 2,175 rows.
- **Observation.** Tracking No, Handled By, Ready Date, Released Date, Days in Center and Customer Option are empty in the file supplied, so storage and delivery fees compute to nothing. Shelf No is filled on 205 rows and Shelf Position on 38.

### 4.9 Payment\_Approval

- Columns: Tracking No, Customer Name, Invoice No, Total Charges, Payment Status (looked up from Accounting), Approval Status (Approved or On Hold), Approved By, Approval Date, Remarks.
- **Observation.** 499 rows are prepared with a formula and no data. **No approvals are recorded**, yet the SOP says "No shipment leaves without Approved status". This control exists on paper only.

### 4.10 Accounting\_Invoicing

- Columns: Tracking No, Customer Name, Invoice No, Invoice Date, Freight Charges, Storage Charges, Delivery Charges, Total Charges, Payment Status, Payment Date, Payment Method, Prepared By, Remarks.
- **Observation.** Payment Status shows Paid on 400 rows and Pending on 5, but Freight Charges, Invoice No, Invoice Date, Payment Method and Payment Date are empty. Storage and Delivery are 0. **No revenue is recorded.**
- **Observation.** The total formula on the first row is wrong (it adds the next row's total instead of delivery charges).
- There is no currency, no VAT or tax field, no discount, no credit note, no customer statement and no cost or expense record.

### 4.11 Delivery\_Register

- Columns: Delivery No, Tracking No, Customer Name, Phone, Delivery Zone, Delivery Fee, Delivery Address, Driver Name, Vehicle/Pikipiki No, Release Date, Time Released, Delivery Status, Date Delivered, Customer Receiver Name, Customer Signature/OTP, Driver Signature, Remarks.
- 82 deliveries (DEL-2026-06-0001 onwards), all but one marked Delivered. Zones: A 51, B 18, C 13. One driver (Ubaya) on one vehicle plate (MC 515 FMQ) for nearly all.
- **Observation.** Delivery fees are inconsistent inside each zone. Zone A shows 4,000 (23 times), 6,000 (20), 0 (3) and outliers of 40,000, 50,000, 100,000 and 120,000. Zone B is 7,000. Zone C is 10,000 plus 50,000 and 100,000. Without a fixed rate table, delivery revenue cannot be trusted.
- **Observation.** None of the 82 deliveries has a Tracking No, a Receiver Name or a Customer Signature/OTP. **Proof of delivery is therefore not recorded.**

### 4.12 SOP

The SOP gives sound rules that the ERP must enforce by design: every box has a number; trips list every box; Tanzania receiving only after cargo arrives and segregation; warehouse records shelf and status; accounting enters freight and invoices; approval before release; driver completes the register before and after delivery; no monthly tabs; colour codes for status; the first 3 days of storage are free.

### 4.13 CRM template (11 sheets, blank)

- **CRM Dashboard**: Total Leads, Leads Today, Leads This Month, Converted, Conversion Rate, Pending and Overdue Follow-Ups, Lost, Revenue Generated, plus counts by lead source and by service.
- **Lead Register** (20 fields): Lead ID, date, name, phone, email, company, customer type, city/region, source, service interested, requirement, estimated value, status, assigned staff, next follow-up, last contact, converted date, revenue, lost reason, remarks.
- **Follow Ups, Communication Log, Quotations, Sales Pipeline, Customer Database, Corporate Leads, Staff Performance**: each defined with its own fields (see sections 12.1 and 17).
- **Dropdown Lists** hold: 14 lead sources (including Walk-In Distribution Center, Walk-In Uhuru Heights, WhatsApp, Phone, Facebook, Instagram, Website, Referral, Email, Event, Google Search, Marketplace Inquiry); 14 services (Air Cargo, Sea Cargo, Courier, E-Commerce Import, Buy For Me, Xerin Marketplace, Express Delivery, Customs Clearance, Charter Flight, Corporate Logistics, National E-Commerce Gateway, Storage and Fulfillment, Other); 8 lead statuses; follow-up and quotation statuses; 8 lost reasons; 5 customer types. The Staff list is empty.
- **Observation.** The CRM and the operations file are not connected. A converted lead does not become a customer ID, a booking or a tracking number. Revenue Generated in CRM is typed, not calculated from invoices.

### 4.14 Architecture PDF

The blueprint defines: a customer self-service portal (register, OTP, supplier details, booking, pickup quote, tracking, invoices); a central ERP database with Customer ID, Booking Reference, Package IDs, Tracking Number, Trip/AWB and status event log; modules for CRM and Sales, Dubai Receiving, Consolidation, Cargo Dispatch, Tanzania Clearance, Distribution Center, Finance and Delivery; 14 shipment status events; notification templates; roles; and acceptance tests. This specification keeps that design and extends it with what the Excel review shows is missing: the passenger-carry (MCO) trip model, two-branch accounting, costs and profit, ageing, release control and management analytics.

## 5. How the process works today (as found in the files)

1. **Dubai receiving.** Staff log a parcel (description, KG, pieces, supplier) in Dubai\_Receiving.
2. **Consolidation.** Parcels are packed into boxes (target 23 KG) listed in Consolidation\_Boxes and Box\_Contents.
3. **Trip.** A passenger carries boxes (MCO); the trip and its boxes are listed in Trip\_Manifest.
4. **Tanzania receiving.** Faith logs arrivals in Goods\_Received\_TZ after segregation.
5. **Warehouse.** Hashim assigns shelf and position and tracks status and days in centre.
6. **Invoice.** Sabrina enters freight, and storage and delivery are pulled from the warehouse sheet.
7. **Approval.** Jacqueline sets Approved or On Hold.
8. **Release and delivery.** The driver completes the Delivery\_Register; customers collect at Tazara or Uhuru Heights, or receive home or office delivery.
9. **Reporting.** The Dashboard counts rows.

In practice each hand-off is a separate manual entry, so the same parcel is typed up to six times and the link between steps is a tracking number that is mostly "N-A".

## 6. Critical findings

| # | Finding | Why it matters | ERP answer |
| --- | --- | --- | --- |
| 1 | No usable tracking number (about 89% "N-A") | Customers cannot be told where cargo is; lost cargo cannot be investigated | System-generated tracking number at the moment of receiving; no receiving without it |
| 2 | No customer identity in receiving data | Cannot invoice, notify or build customer history | Customer ID mandatory before receiving; booking reference links supplier to customer |
| 3 | No revenue and no cost data | CEO cannot see profit | Rate cards, invoices, payments, cost ledger, profit per shipment |
| 4 | Weights do not reconcile across Dubai, boxes and Tanzania | Cannot detect shortage, theft or errors | Weight check at each hand-off; variance alerts |
| 5 | Duplicate and inconsistent box numbers | Boxes cannot be matched to trips | System-generated unique box IDs and QR labels |
| 6 | Approval sheet empty | Release control is not working | Hard system block: no release without approved payment |
| 7 | No proof of delivery (no receiver, OTP or signature) | Disputes cannot be defended | OTP or signature plus photo, mandatory to close delivery |
| 8 | Delivery fees inconsistent inside zones | Revenue leakage and disputes | Fixed rate table; overrides need manager approval and a reason |
| 9 | Storage rule differs between SOP and formula; rates in missing sheet | Wrong or disputed storage charges | One rule engine with configurable free days and rate |
| 10 | Free-text and mixed-format data (names, dates, categories) | Reports and month totals unreliable | Dropdowns, validated dates, master data lists |
| 11 | Dashboard formulas misaligned and fixed-range | Management numbers may be wrong | Live queries on the database |
| 12 | CRM disconnected from operations | Sales effort cannot be tied to revenue | Lead to customer to booking to invoice in one chain |
| 13 | No audit trail | Anyone can change any cell silently | Immutable log of every change: who, when, old value, new value |
| 14 | Customer personal data in a shared workbook | Privacy and security risk | Role-based access, encryption, retention rules |

## 7. Vision and design principles

The ERP is one platform with one database. The following principles apply to every module:

1. **One entry, many uses.** A parcel is typed once, at the place where it physically appears. Every later screen, invoice and report reads that record.
2. **Nothing moves without an ID.** No cargo is received, boxed, carried, shelved, released or delivered without a system tracking number and a customer ID.
3. **Every status is evidence.** A status change records time, place, staff member, device and, where required, a photo, weight, signature or OTP.
4. **Controls are built in, not written in an SOP.** Release without approved payment is blocked by the system, not by a reminder.
5. **Two branches, one company.** Every record carries a branch. The CEO sees the group; each branch manager sees their own branch; staff see only what their role needs.
6. **Money is tracked from quote to cash.** Quote, invoice, payment, cost, margin and cash balance are connected.
7. **Master data, not free text.** Item types, suppliers, zones, staff, shelves, airlines and statuses come from controlled lists managed by an administrator.
8. **Audit everything.** No record is silently overwritten. Corrections create a new version with a reason.
9. **Customer-facing by default.** Customers can book, pay, track and download documents without calling the office.
10. **Built for growth.** Air, sea, courier, e-commerce, buy-for-me and marketplace services can be added without redesigning the database.

## 8. Company structure: Dubai branch and Tanzania branch

### 8.1 Entities, branches and locations

| Level | Value | Notes |
| --- | --- | --- |
| Company | Xerin Group Limited | Parent; group reporting |
| Branch 1 | Dubai (UAE) | Dubai Office, Dubai Warehouse |
| Branch 2 | Tanzania | Tazara collection point, Uhuru Heights distribution centre, Xerin HQ, and any future regional point |
| Location | Any physical place that holds cargo | Each location has shelves; stock is always "at" a location |

Every transaction (parcel, invoice, payment, expense, delivery) stores a branch and a location. Adding a third branch later (for example Nairobi or Guangzhou) is a configuration task, not a rebuild.

### 8.2 Currencies

- Transaction currencies: AED (Dubai), TZS (Tanzania), USD (reference and corporate customers).
- A daily exchange-rate table is maintained by Finance. Every invoice, payment and expense stores its own currency, the rate used and the amount in the group reporting currency (to be decided; USD recommended).
- Dashboards can be shown in any of the three currencies.

### 8.3 Branch accounting rule (decision required)

Cargo is bought in Dubai and sold in Tanzania, so revenue and cost fall in different branches. Management must decide how profit is attributed. The system supports either approach:

- **Option A (recommended): shared shipment margin.** Revenue is booked where the customer pays; each shipment shows all costs from both branches; branch profit is shown after an agreed inter-branch charge for Dubai handling.
- **Option B: separate branch ledgers** with an inter-branch invoice for each trip or box.

The ERP includes an **inter-branch ledger** so that money Dubai spends (packing, airline, passenger fee) and money Tanzania collects can be reconciled each month.

## 9. Target end-to-end flow

### 9.1 Stages

| # | Stage | What happens | Main record | Owner | Required evidence | Customer message |
| --- | --- | --- | --- | --- | --- | --- |
| 0 | Enquiry | Lead enters from WhatsApp, phone, walk-in, web or referral | Lead | Sales | Source, contact | Auto-acknowledgement |
| 1 | Quote | Sales issues a quote from the rate card | Quotation | Sales | Price, validity date | Quote sent |
| 2 | Customer account | Customer registered, phone verified by OTP, ID or business details if required | Customer | Sales / customer | OTP, ID document where needed | Welcome |
| 3 | Booking | Customer or staff creates a booking and gets a reference and QR | Booking | Customer / Sales | Terms accepted | Booking confirmed |
| 4 | Collection | Free drop-off at Dubai office, or paid supplier pickup | Pickup job | Dubai Ops | Pickup time, driver | Pickup scheduled |
| 5 | Dubai receiving | Staff scan the booking, weigh, photograph, label and accept the parcel; tracking activated | Package | Dubai Receiving | Weight, photos, label | Cargo received, tracking number |
| 6 | Verification | Final weight, dimensions, goods check, prohibited-item screening, pre-invoice | Package check | Dubai Warehouse | Photos, check list | Cargo verified, invoice ready |
| 7 | Consolidation | Package assigned to a box (or pallet or ULD) | Box and Box Line | Cargo Ops | Box weight, QR box label | Assigned for consolidation |
| 8 | Dispatch planning | Choose carriage mode: passenger carry (MCO trip), air cargo (AWB), sea container | Trip / AWB / Container | Cargo Ops | Manifest | Ready for dispatch |
| 9 | Handover and departure | Carrier or passenger receives boxes; departure confirmed (not just handover) | Trip or AWB event | Cargo Ops | Signed handover, boarding or AWB proof | Handed to carrier; later "Departed" |
| 10 | Arrival and segregation | Tanzania counts boxes, opens, matches each package to the manifest, records shortages | Receiving session | Tanzania Ops | Count, weight, photos, discrepancy report | Arrived Tanzania |
| 11 | Clearance | Customs documents, duty and tax, release | Clearance file | Clearance | Entry number, duty receipt, release | In clearance; released |
| 12 | Distribution centre | Shelving and customer option chosen (collect or deliver) | Stock record | DC Officer | Shelf, position, photo | Received at DC; ready |
| 13 | Invoice and payment | Final invoice (freight, duty, storage, delivery); payment recorded | Invoice, Payment | Finance | Receipt, method, reference | Invoice, payment received |
| 14 | Release approval | System checks payment, approval and identity; release code generated | Release approval | Finance / Approver | Approval record | Ready for collection |
| 15 | Delivery or collection | Collected at DC, or driver delivers; receiver verified | Delivery | Driver / DC | OTP or signature, photo, receiver name | Delivered |
| 16 | Closure | Feedback, claims window, ageing and unclaimed handling | Case | Customer care | Survey, claim record | Thank you |

### 9.2 Carriage modes

The Excel file shows passenger-carried boxes (the MCO model). The PDF assumes airline cargo. The ERP supports all of these as configurable carriage modes, so one booking can use different modes:

- **Passenger carry (MCO).** Trip created per passenger and flight. The passenger's name, ID, airline, flight number, flight date and allowance are recorded. Every box is listed on the trip manifest. Handover to the passenger is signed in Dubai and handover from the passenger is signed in Tanzania. The trip is not marked "Departed" until departure is confirmed.
- **Air cargo (AWB).** Master AWB and house AWBs, airline, flight, chargeable weight, airline rate, handling fees, and proof of uplift.
- **Sea cargo.** Container or LCL booking, bill of lading, vessel, ETD and ETA, container number and seal.
- **Courier and express.** Third-party waybill with tracking-number reference and cost.

### 9.3 Exception paths (must be designed, not improvised)

- **Unmatched parcel in Dubai** (no booking or customer): received into an "Unmatched" queue with a temporary tracking number, reported daily, escalated after a set number of hours, and blocked from consolidation until matched.
- **Damage on arrival or in transit:** photo, report, claim file, insurance or compensation decision.
- **Shortage or surplus at Tanzania receiving:** discrepancy report against the manifest; automatic alert to Dubai and management.
- **Offload or delayed flight:** event "Delayed / offloaded", revised schedule, customer message.
- **Wrong customer or wrong box:** correction with reason, approver and audit entry.
- **Customs hold, valuation query or seizure:** clearance case with documents and costs.
- **Unclaimed goods:** after a configurable number of days the item becomes "Old Stock", escalates, and follows a disposal or auction policy approved by management.
- **Return or re-export:** reverse booking linked to the original.
- **Disputed charges or delivery:** case with attachments and resolution.

## 10. Identifiers and data relationships

All identifiers are generated by the system. Staff never type or reuse them. Each has a barcode or QR code where physical labelling is needed.

| Identifier | Format (proposal) | Created when | Notes |
| --- | --- | --- | --- |
| Customer ID | XC-000001 | Registration | One per person or company; individuals can belong to a company account |
| Lead ID | LD-2026-00001 | Enquiry | Converts to Customer ID |
| Quote No | QT-2026-00001 | Quote issued | Linked to rate card version |
| Booking Ref | BK-2026-000001 | Booking confirmed | Shared with supplier; QR |
| Package ID | PK-000000001 | Physical receipt in Dubai | One per physical parcel |
| Tracking No | XRN + 9 digits | Same time as Package ID | The number the customer sees; replaces "N-A" |
| Box ID | BX-DXB-2026-00001 | Box created | Unique across the company; replaces HSR / YALLA / XRN numbering |
| Trip No | TR-2026-0001 | Trip created | Passenger trip |
| AWB / BL / Container | Carrier-issued | Dispatch | Stored as given |
| Receiving session | RC-TZ-2026-00001 | Box opened in Tanzania | Links to manifest |
| Clearance file | CL-2026-00001 | Clearance starts |  |
| Invoice No | INV-DXB-2026-00001 / INV-TZ-2026-00001 | Invoice posted | Sequential per branch; no gaps |
| Receipt No | RCP-2026-00001 | Payment recorded |  |
| Release Code | 6-digit OTP | Release approved | Sent to customer; verified at delivery |
| Delivery No | DEL-2026-11-0001 | Delivery job created | Continues the format already in use |

**Relationship chain (matches the PDF).** Customer ID has many Booking References. A booking has one or more Package IDs. A package belongs to one box (or is packed loose) and travels on one or more legs (trip, AWB, container). Each leg has an event history. The chain ends in an invoice, a payment, a release approval and a proof of delivery. A booking can contain packages that travel separately; the booking closes only when every package is closed.

## 11. Status model

The 14 events in the blueprint are kept. The following lists extend them with the statuses found in the Excel dropdowns and the exception paths above.

**Package status (customer-visible):** Booking confirmed; Pickup scheduled / Awaiting drop-off; Received in Dubai; Verified; Consolidated; Ready for dispatch; Handed to carrier; Departed Dubai (verified); Delayed / Offloaded; Arrived Tanzania; In customs clearance; Customs released; Received at distribution centre; On shelf; Invoiced; Ready for collection; Transfer to Uhuru Heights; Out for delivery; Delivered; Collected; On hold; Old stock; Returned; Closed.

**Box status:** Open; Closed (sealed and weighed); Assigned to trip; Handed to passenger or carrier; Departed; Arrived; Opened and segregated; Closed out.

**Trip status:** Planned; Boxes assigned; Handed over; Departed (verified); Arrived; Reconciled; Closed.

**Invoice status:** Draft; Issued; Part-paid; Paid; Overdue; Written off; Cancelled (credit note).

**Approval status:** Pending; Approved; On hold (reason mandatory); Rejected.

**Delivery status:** Scheduled; Released to driver; Out for delivery; Delivered; Failed (reason); Returned to DC.

**Rule:** a status can only move along allowed transitions. For example, "Departed" cannot be set unless a handover record exists; "Out for delivery" cannot be set unless approval is "Approved".

## 12. Module specifications

### 12.1 CRM and Sales

**Purpose.** Capture every enquiry, follow it up, quote it, convert it into a customer and a booking, and measure sales performance, all linked to real revenue.

**Screens and data (built from the CRM template):**

- **Lead Register:** Lead ID, date, name, phone, email, company, customer type (Individual, SME, Corporate, Government, International), city or region, source, service interested, requirement summary, estimated value, status, assigned staff, next follow-up, last contact, converted date, revenue generated (calculated from invoices, not typed), lost reason, remarks.
- **Lead sources:** Walk-In Distribution Center, Walk-In Uhuru Heights, WhatsApp, Phone Call, Facebook, Instagram, Website, Referral, Email, Event, Google Search, Marketplace Inquiry, Other. Add Dubai walk-in and Dubai supplier referral.
- **Services:** Air Cargo, Sea Cargo, Courier, E-Commerce Import, Buy For Me, Xerin Marketplace, Express Delivery, Customs Clearance, Charter Flight, Corporate Logistics, National E-Commerce Gateway, Storage and Fulfillment.
- **Lead status:** New Lead, Contacted, Quotation Sent, Negotiation, Awaiting Payment, Converted, Lost, Dormant.
- **Follow-ups:** ID, lead, staff, date and time, reason, status (Pending, Completed, Overdue, Rescheduled), outcome, next follow-up. The system creates reminders and flags overdue follow-ups automatically.
- **Communication log:** date, start and end time, duration, lead or customer, channel (Phone, WhatsApp, Email, Walk-In, Website, Referral, Social Media, Other), subject, customer request, response, staff, next action, escalated to, status.
- **Quotations:** number, date, lead, service, description, amount, currency, status (Draft, Sent, Accepted, Rejected, Expired), sent by, valid until, accepted date, converted revenue. Amounts come from the rate card; manual discounts require approval.
- **Sales pipeline:** stage, probability, expected close date, weighted value, last activity, next action.
- **Customer database:** customer type, company, city, primary service, lifetime revenue (calculated), status, assigned account manager, credit terms, preferred delivery option.
- **Corporate leads:** company, contact person, position, industry, service interest, source, owner, status.
- **Staff performance:** leads assigned, contacted, quotes sent, converted, lost, revenue, pending follow-ups, conversion rate; all calculated from live data.

**Rules.** A lead cannot be closed as Lost without a reason. A converted lead creates the customer record automatically. Duplicate detection runs on phone number and email.

### 12.2 Customer portal and mobile app

- Registration with phone or email and OTP; saved sender, receiver and delivery addresses.
- Supplier details and order numbers (Amazon, Noon, Shein, Temu and so on) attached to a booking.
- Instant quote from the rate card; free drop-off or paid pickup choice.
- Booking confirmation with QR code and reference to give the supplier.
- Live tracking with the full milestone list; documents (invoice, receipt, delivery note) downloadable.
- Payment: mobile money, card, bank transfer reference; payment status shown.
- Choice of collection point or home and office delivery with fee shown before payment.
- Notifications by SMS, WhatsApp and email, one message per event, with delivery and retry log.
- Language: English and Swahili.

### 12.3 Dubai receiving (replaces Dubai\_Receiving)

**Fields.** Receiving ID, branch, location (Dubai Office or Dubai Warehouse), date and time received (automatic), booking reference, customer ID, supplier (list), supplier order or waybill number, supplier contact, item description, item category (list), quantity, pieces and package type, declared value, weight (from scale), dimensions, photos (mandatory), condition, received by (automatic from login), remarks, label printed.

**Rules.**

- A tracking number is generated at acceptance. There is no manual entry and no "N-A".
- If no booking exists, the parcel goes to the Unmatched queue (section 9.3).
- Weight must come from the scale record (or be flagged "manual" with a reason).
- Dubai screening list for prohibited and restricted items (batteries, liquids, medicines, perfumes and similar, as agreed with airlines and Tanzanian customs); flagged items require a supervisor decision.
- Supplier performance is measured: number of parcels, damage, delays and unmatched rate by supplier.

### 12.4 Consolidation, boxes and trips (replaces Consolidation\_Boxes, Box\_Contents, Trip\_Manifest)

**Box.** Box ID, date packed, packed by, location, status, target weight (default 23 KG, configurable per carriage mode), actual weight, box type, seal number, QR label, trip or AWB or container, remarks.

**Box line.** Box ID, package ID, tracking number, customer, item category, KG, pieces. A package can be split across boxes only through a controlled "split" action.

**Trip (passenger carry).** Trip No, passenger, ID document, phone, airline, flight number, flight date, baggage allowance, boxes assigned, total KG, passenger fee agreed, handover signed in Dubai, handover signed in Tanzania, departure confirmed, arrival confirmed, status.

**Rules.**

- Box IDs are unique across the company. The old HSR, YALLA and XRN numbers are kept as "legacy reference" for migration only.
- A box cannot be assigned to a trip unless it is Closed, sealed and weighed.
- The system warns when a trip exceeds the passenger's allowance.
- Total box weight must equal the sum of its box lines (tolerance configurable); otherwise the box cannot close.
- Departure requires evidence (boarding pass or airline confirmation). The status "Departed" is only set after this evidence is attached.

### 12.5 Tanzania receiving and segregation (replaces Goods\_Received\_TZ)

**Fields.** Receiving session, trip or AWB, box ID, arrival date and time, received by, box weight on arrival, packages found, packages expected from the manifest, discrepancy type (missing, extra, damaged, wrong box), photos, customer ID, tracking number, item category, KG, pieces, status.

**Rules.**

- Receiving is by scanning each box, then each package. The system lists expected packages and highlights any not scanned.
- Weight difference against the Dubai box weight above the tolerance creates an alert.
- Packages found without a manifest line are flagged "Extra / unmanifested" and sent to the Unmatched queue.
- Received By is automatic from the login; free-text names are not allowed.

### 12.6 Customs clearance (new module; the PDF lists it, the Excel file has none)

Clearance file per consignment: consignment type, entry or declaration number, HS codes, invoice values, duty and tax assessed and paid, agent or broker, documents (airway bill or passenger carry declaration, commercial invoices, permits), clearance status, release date, costs, exceptions. Duty and tax paid on behalf of customers is recorded as a recoverable cost and re-invoiced. Customs system integration is to be confirmed with the Tanzania Revenue Authority requirements.

### 12.7 Warehouse and distribution centre (replaces Warehouse\_Management)

**Fields.** Stock record per package, location, shelf number, shelf position (zone, rack, level), date and time received, days in centre (calculated), chargeable days, storage charge, customer option (Collect at Tazara, Collect at Uhuru Heights, Home or Office delivery), ready date, released date, handled by, status, remarks.

**Rules.**

- Shelf and position are mandatory before a package can be marked On Shelf.
- Storage: first **3 free days** (as in the SOP), then a rate per KG per day. Free days and rate are configurable values with an effective date and history (the value found in the current formula is 500 per KG per day after 4 days; management must confirm).
- Ageing alerts at configurable days (for example day 3, 7, 14, 30). After the unclaimed limit the package becomes Old Stock.
- Stock counts (cycle counts) are recorded in the system; differences create adjustment records with a reason and approver.
- Colour codes from the SOP are kept: On Shelf green, Out for Delivery purple, Old Stock red, Delivered, Collected and Closed gray.

### 12.8 Release approval (replaces Payment\_Approval)

The approval check is automatic and manual together: the system confirms the invoice is fully paid (or credit is approved), then an approver confirms release. An approval record stores approver, date and time, decision, reason for hold and the release code. **Rule:** the Release Code cannot be generated, and delivery cannot start, without Approved status. Credit release for corporate customers requires a credit limit check and approval by Finance.

### 12.9 Delivery and last mile (replaces Delivery\_Register)

**Fields.** Delivery No, tracking numbers on the run, customer, phone, delivery zone, delivery address and map pin, delivery fee (from the rate table), driver, vehicle or pikipiki plate, release date and time, status, date delivered, receiver name, OTP or signature, photo at drop-off, driver signature, failed-delivery reason, cash collected on delivery, remarks.

**Rules.**

- Delivery fee is looked up from the zone rate table. Any different amount needs a reason and manager approval.
- Delivery cannot be closed without receiver name and OTP or signature. The OTP is sent to the customer by the system.
- Drivers see only their own jobs on a phone app, with route order and customer contact.
- Driver reports: deliveries per day, success rate, failed reasons, cash handed in, fuel and cost per delivery.

### 12.10 Claims, returns and customer care

Case register for complaints, damage claims, lost-item investigations, price disputes and returns. Each case links to a tracking number, has an owner, due date, evidence, resolution and cost. Compensation paid appears in the profit report.

### 12.11 Later-phase service modules

The CRM service list includes Buy For Me, Xerin Marketplace, National E-Commerce Gateway, Charter Flight and Storage and Fulfillment. The data model reserves space for these (product orders, purchase from supplier, marketplace sellers, charter bookings, fulfilment inventory) so they can be switched on in later phases without changing the core.

## 13. Data model

### 13.1 Core entities

| Entity | Purpose | Key fields | Relations |
| --- | --- | --- | --- |
| Branch, Location | Dubai, Tanzania; office, warehouse, DC, collection point | name, country, currency, address, active | Location belongs to Branch |
| Staff, Role | People and permissions | name, phone, branch, role, status, MFA | Staff has roles; every record stores created by |
| Customer | One identity per person or company | customer ID, type, name, phone, email, company, ID document, credit terms, status | Has addresses, bookings, invoices |
| Address | Pickup, delivery, receiver | label, area, zone, map pin | Belongs to Customer |
| Lead, FollowUp, CommLog | CRM activity | per CRM template | Lead converts to Customer |
| Quotation, QuotationLine | Price offers | number, rate card version, amount, currency, validity | Lead or Customer; can convert to Booking |
| Booking | Customer's shipment request | reference, customer, service, carriage mode, origin, destination, collection type, status | Has Packages |
| Package | One physical parcel | package ID, tracking no, booking, supplier, description, category, weight, dimensions, pieces, photos, status, current location | In one Box Line; has Events |
| PackageEvent | Status history (immutable) | package, status, time, staff, branch, location, device, evidence, notification result | The tracking timeline |
| Box, BoxLine | Consolidation unit and its contents | box ID, weight, seal, status; box line = package, KG | Box belongs to a Leg |
| Trip | Passenger-carry journey | trip no, passenger, flight, allowance, fee, status | Has Boxes |
| Leg (Shipment leg) | One movement: trip, AWB or container | mode, carrier, reference, ETD, ETA, departed, arrived, cost | Has Boxes or Packages |
| ReceivingSession | Tanzania arrival check | leg, box, expected, found, discrepancies | Creates Stock Records |
| ClearanceFile | Customs | entry no, HS codes, duty, tax, agent, status, release | Links to Leg and Packages |
| StockRecord, Shelf | Warehouse position | package, location, shelf, position, in date, out date, status | Shelf belongs to Location |
| RateCard, RateLine | Price rules with versions | service, lane, mode, customer type, category, unit, price, minimum, effective from and to | Used by Quote, Invoice |
| DeliveryZone, ZoneRate | Last-mile pricing | zone, areas, fee, effective dates | Used by Delivery |
| Invoice, InvoiceLine | Charges to customer | invoice no, branch, currency, lines, tax, total, status, due date | Customer, Booking, Packages |
| Payment, Allocation | Money received | receipt no, method, reference, amount, currency, FX rate, received by, bank or wallet | Allocated to invoices |
| CreditNote, Refund, WriteOff | Corrections | reason, approver, linked invoice |  |
| Vendor, Bill, BillPayment | Suppliers of Xerin (airline, agent, passenger, landlord) | vendor, bill no, amount, due, status | Costs link to Leg, Trip or Branch |
| Expense, PettyCash | Operating costs | category, amount, branch, approver, receipt image | Cost center |
| Account, JournalEntry | General ledger | account code, debit, credit, date, source document | Posted from Invoice, Payment, Bill |
| ExchangeRate | Daily FX | date, from, to, rate | Used on every money record |
| Delivery, Driver, Vehicle | Last mile | per section 12.9 | Packages, Zone |
| Release (Approval) | Release control | package or invoice, decision, approver, code | Required for Delivery |
| Case | Claims, complaints, returns | type, owner, status, cost | Package, Customer |
| Notification, NotificationLog | Customer messages | template, channel, sent, delivered, retries | Package Event |
| Attachment | Photos, scans, signatures | file, hash, uploader, time | Any record |
| AuditLog | Every create, edit, delete, login | who, when, what, old, new, reason | All records |
| MasterData | Controlled lists | list name, value, active | Used by dropdowns |

### 13.2 Master data to create at go-live

- **Item categories (clean list):** Clothes, Shoes, Accessories, Cosmetics and Perfume, Electronics, TV, Spare Parts, Supplements, General Cargo, Online Parcel, Mixed Items, Others. The Excel file contains spelling variants (cloths, clothes, cosmatic, cosmetics, spare, spare part, suppliments) which are mapped to this list during migration. The category "ELI" appears in the Dashboard with no definition; management must say what it means.
- **Suppliers:** Amazon, Noon, Shein, 6th Street, Brands for Less, Temu, Aramex, Zara, TOPTEX, Papa and Mama, H&M, Namshi, Next, Other.
- **Locations:** Dubai Office, Dubai Warehouse, Tazara, Uhuru Heights, Xerin HQ.
- **Delivery zones:** Zone A, Zone B, Zone C, with named areas (from the register: Upanga, Msasani, Masaki, Posta, Mnazi Moja, Makumbusho, Shekilango, Uhuru Heights, Xerin HQ).
- **Airlines, payment methods, units, currencies, lost reasons, case types, vehicle types.**
- **Staff and roles** using the names in the SOP as the starting users.

## 14. Business rules and pricing engine

All prices and rules live in configuration tables with an effective date. Nothing is hard-coded in formulas. Old invoices keep the rate that applied on the day.

### 14.1 Rate card

- **Freight:** price per KG by service (air passenger-carry, air cargo, sea), lane (Dubai to Tanzania), customer type (individual, SME, corporate), item category if different, with a minimum charge. The Excel file contains no freight prices, so management must supply the current tariff.
- **Chargeable weight:** greater of actual and volumetric weight, using a configurable divisor.
- **Pickup fee (Dubai):** by supplier area or distance band, in AED.
- **Handling, packing and re-packing fees**, optional insurance (percent of declared value), special handling for fragile or restricted items.
- **Customs and duty:** recorded separately as pass-through cost plus a service fee.
- **Discounts:** by customer or volume, with a maximum percent per role; above the limit needs approval.
- **Tax:** VAT or sales tax per branch and per invoice line as required. Current UAE and Tanzanian rates and fiscal-receipt requirements must be confirmed with the company's tax adviser before go-live.

### 14.2 Storage

- First **3 days free** (SOP). The Excel formula in Goods\_Received\_TZ uses a 4-day threshold and 500 per KG per day, while Warehouse\_Management reads its values from the missing Config\_Lists sheet. The ERP has **one** rule: free days and daily rate per KG, with effective dates, and the rule shown on the customer's invoice and in the notification.
- Storage runs from the date and time of receipt at the distribution centre until release.
- Waiver of storage requires a reason and manager approval.

### 14.3 Delivery and collection fees

From the formulas and register: Collect at Tazara is free; Collect at Uhuru Heights is TZS 5,000; Home or Office delivery is TZS 10,000 in the warehouse formula, while the register shows zone-based fees (A mostly 4,000 to 6,000, B 7,000, C mostly 10,000) with several unexplained larger values. The ERP uses a single zone rate table. Management must confirm the fee for each zone and each customer option.

### 14.4 Release rule

Release requires: package On Shelf or Ready; invoice fully paid or approved on credit; approver decision "Approved"; identity or OTP match at hand-over. Any exception is logged with the approver.

### 14.5 Weights and rounding

Weights are stored to 0.1 KG and invoices round according to one configured rule. Box target weight (23 KG) and tolerance are configurable; weight variance thresholds between Dubai and Tanzania are configurable.

## 15. Finance and accounting

### 15.1 Principles

- Double-entry general ledger with separate books for the Dubai and Tanzania branches and a consolidated group view, following the accounting framework Xerin's auditors use (confirm with the auditor).
- Every posted document creates journal entries automatically. Posted entries cannot be edited; corrections use credit notes, reversals or adjustments with approval.
- Accounting periods close monthly; closed periods are locked.

### 15.2 Revenue streams

Freight, pickup, handling and packing, storage, delivery and transfer, customs service fee, insurance, other fees, plus margin on Buy For Me and marketplace sales when those services start. Duty and tax paid for the customer is recorded as a recoverable receivable, not as revenue.

### 15.3 Cost categories (none of these exist in the Excel file today)

| Cost group | Examples | Tied to |
| --- | --- | --- |
| Transport | Airline cargo charges, passenger carry fees or allowances, sea freight, courier | Trip, AWB, Container |
| Dubai operations | Packing materials, pickup vehicles and fuel, warehouse rent share, Dubai staff, scales and printing | Branch, allocated by KG |
| Customs and clearance | Duty and tax paid, clearing agent fees, port or airport charges, inspection | Clearance file |
| Tanzania operations | DC rent, shelving, utilities, DC staff, security | Branch, allocated by KG or day |
| Last mile | Driver pay, fuel, vehicle maintenance, pikipiki costs | Delivery |
| Financial | Mobile-money fees, bank charges, card fees, FX loss | Payment |
| Claims | Compensation, damaged or lost goods, insurance excess | Case |
| Overheads | Salaries not allocated, marketing, software, rent, licences | Cost center |

Direct costs attach to a trip, AWB or delivery and flow to the shipment. Shared costs are allocated by a configured method (per KG, per shipment or per day).

### 15.4 Invoice to cash

- Invoice per booking or per package group, in the customer's chosen currency, with freight, storage, delivery, duty, tax and discounts as separate lines.
- Payment methods: cash, mobile money, bank transfer, card, POS. Each payment records reference, payer, date and who received it, and can be part-payment or advance deposit.
- Receipts are numbered and generated automatically; where Tanzanian law requires a fiscal receipt device (EFD), the system integrates with it or records its receipt number (to be confirmed with the tax adviser).
- Refunds, credit notes and write-offs need approval and a reason.

### 15.5 Receivables

Customer statements, credit limits and credit terms for corporate accounts, ageing buckets (0 to 7 days, 8 to 30, 31 to 60, over 60), automatic reminders by SMS or WhatsApp and escalation to Sales or Finance. **Cargo on credit cannot be released if the customer is over the credit limit.**

### 15.6 Payables

Vendors (airlines, passengers, agents, landlords, suppliers) with bills, due dates, approval limits by role, payment runs and a payables ageing report.

### 15.7 Cash, bank and mobile money

- Separate cash books for each branch and each cashier; bank accounts and mobile-money wallets as ledger accounts.
- **Daily cash close:** the cashier counts cash, the system shows expected cash, the difference is recorded and approved. Collections banked the same day are matched to bank statements.
- Petty cash with receipt photo and approver.
- Foreign exchange gains and losses are posted automatically.

### 15.8 Profitability (the CEO's main question)

- **Shipment profit** = invoiced revenue (excluding tax and pass-through duty) minus direct transport cost, handling cost, clearance cost, delivery cost, financial fees, claims, and allocated shared cost.
- **Trip or AWB profit** = revenue of all packages on the leg minus the cost of that leg.
- **Customer profit**, **service profit**, **route profit**, **branch profit** and **staff-sales profit** are sums of shipment profit grouped by the relevant field.
- **Gross margin %** and **net margin %** are shown at every level, with a drill-down to the invoices and bills behind each number.

### 15.9 Financial controls

Segregation of duties (the person who records a payment cannot approve a write-off), approval limits by role, mandatory attachments for expenses above a limit, locked periods, daily cash variance report, monthly branch reconciliation of the inter-branch ledger, and an exception report for manual price overrides, waived storage and deleted drafts.

### 15.10 Chart of accounts (outline)

| Group | Accounts (examples) |
| --- | --- |
| Assets | Cash by branch, bank accounts, mobile-money wallets, accounts receivable, duty recoverable, prepayments, inter-branch receivable, equipment |
| Liabilities | Accounts payable, customer deposits, accrued costs, tax payable, inter-branch payable |
| Equity | Capital, retained earnings |
| Revenue | Freight, pickup, handling, storage, delivery, customs service, insurance, other |
| Direct costs | Airline, passenger fees, sea freight, packing, clearance, last mile, claims |
| Operating costs | Salaries, rent, utilities, marketing, software, depreciation, bank and mobile fees |
| Other | FX gain or loss, write-offs |

## 16. Roles, permissions and access

### 16.1 Roles

Customer, Sales and CRM, Dubai Receiving, Dubai Warehouse and Cargo Operations, Tanzania Receiving, Clearance, Distribution Centre Officer, Finance and Accounting, Release Approver, Delivery (Driver and Dispatcher), Customer Care, Branch Manager (Dubai, Tanzania), CEO and Management, CTO and Admin, Auditor (read-only).

### 16.2 Starting assignments (from the SOP)

| Current owner | Role in the ERP |
| --- | --- |
| Ezekiel and Dubai team (Faisal, Amina, Conrad) | Dubai Receiving, Cargo Operations |
| Faith, Abdul, Wahida | Tanzania Receiving |
| Hashim | Distribution Centre Officer |
| Sabrina | Finance and Accounting |
| Jacqueline | Release Approver |
| Ubaya and other drivers | Delivery |
| Joseph | CTO and Admin |

### 16.3 Permission matrix

Key: **F** full (create, edit, approve within rules), **E** create and edit own branch, **V** view, **A** approve only, **—** no access.

| Role | CRM | Dubai receiving | Boxes and trips | TZ receiving and DC | Clearance | Invoices and payments | Release | Delivery | Costs and profit | Reports |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Customer | Own | Own tracking | — | Own tracking | — | Own invoices | — | Own | — | — |
| Sales and CRM | F | V | — | V | — | V (own customers) | — | V | — | Own sales |
| Dubai Receiving | V | E | V | — | — | — | — | — | — | Branch ops |
| Cargo Operations | — | V | F | V | V | — | — | — | Leg cost entry | Branch ops |
| Tanzania Receiving | — | V | V | E | V | — | — | — | — | Branch ops |
| Clearance | — | — | V | V | F | — | — | — | Clearance cost | Clearance |
| DC Officer | — | — | V | F | — | V | — | E | — | Stock |
| Finance | V | V | V | V | V | F | V | V | F | Finance |
| Release Approver | — | — | — | V | — | V | A | V | — | Release |
| Delivery | — | — | — | V (own jobs) | — | — | V | E (own jobs) | — | Own runs |
| Branch Manager | V | F (own branch) | F (own branch) | F (own branch) | V | V | A | F | V (own branch) | Branch |
| CEO and Management | V | V | V | V | V | V | V | V | V (group) | All |
| CTO and Admin | Config | Config | Config | Config | Config | Config | Config | Config | — | System |
| Auditor | V | V | V | V | V | V | V | V | V | All (read only) |

### 16.4 Security rules

- Login with strong password; multi-factor authentication for Finance, Release Approver, Branch Manager, CEO and Admin.
- Staff see only their own branch unless a role gives group access. Cost and profit fields are hidden from roles without "Costs and profit" access.
- Session timeout, device logging, IP or location alerts, and immediate account disable when staff leave.
- Customer phone numbers and ID documents are visible only to roles that need them; exports are logged.
