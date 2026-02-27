export type Maybe<T> = T | null;
export type InputMaybe<T> = T | null;
export type Exact<T extends { [key: string]: unknown }> = { [K in keyof T]: T[K] };
export type MakeOptional<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]?: Maybe<T[SubKey]> };
export type MakeMaybe<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]: Maybe<T[SubKey]> };
export type MakeEmpty<T extends { [key: string]: unknown }, K extends keyof T> = { [_ in K]?: never };
export type Incremental<T> = T | { [P in keyof T]?: P extends ' $fragmentName' | '__typename' ? T[P] : never };
/** All built-in and custom scalars, mapped to their actual values */
export type Scalars = {
  ID: { input: string; output: string; }
  String: { input: string; output: string; }
  Boolean: { input: boolean; output: boolean; }
  Int: { input: number; output: number; }
  Float: { input: number; output: number; }
  /** Arbitrary JSON value. */
  JSON: { input: any; output: any; }
  /**
   * Loose type that allows any value. Be careful when passing in large `Int` or `Float` literals,
   * as they may not be parsed correctly on the server side. Use `String` literals if you are
   * dealing with really large numbers to be on the safe side.
   */
  Mixed: { input: any; output: any; }
  /** Unix timestamp (milliseconds since Unix epoch) in UTC. Always stored and transmitted as UTC. */
  Timestamp: { input: number; output: number; }
  /** ULID scalar type for Universally Unique Lexicographically Sortable Identifiers */
  ULID: { input: string; output: string; }
};

/** User action taken on an activity item */
export type Activity = {
  __typename?: 'Activity';
  /** Action taken by the user (null if no action taken yet) */
  action?: Maybe<ActivityAction>;
  /** When the action was created */
  created_at: Scalars['Timestamp']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Notes for this activity (paginated) */
  notes: NotePaginator;
  /** Snooze expiration timestamp */
  snoozed_until?: Maybe<Scalars['Timestamp']['output']>;
  /** The targetable entity for this action */
  targetable?: Maybe<ActivityTargetable>;
  /** Trigger that surfaced the item */
  trigger: ActivityTrigger;
  /** When the action was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this action */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};


/** User action taken on an activity item */
export type ActivityNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** Activity state enum */
export enum ActivityAction {
  Archived = 'ARCHIVED',
  Snoozed = 'SNOOZED'
}

/** A paginated list of Activity items. */
export type ActivityPaginator = {
  __typename?: 'ActivityPaginator';
  /** A list of Activity items. */
  data: Array<Activity>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Entities that can be targeted by activities */
export type ActivityTargetable = Audition | Invoice | Job | UsageRight;

/** Activity trigger enum */
export enum ActivityTrigger {
  AuditionResponseDue = 'AUDITION_RESPONSE_DUE',
  InvoiceDueSoon = 'INVOICE_DUE_SOON',
  InvoiceOverdue = 'INVOICE_OVERDUE',
  JobDeliveryDue = 'JOB_DELIVERY_DUE',
  JobRevisionRequested = 'JOB_REVISION_REQUESTED',
  JobSessionUpcoming = 'JOB_SESSION_UPCOMING',
  UsageRightsExpiring = 'USAGE_RIGHTS_EXPIRING'
}

/** Input for adding a note */
export type AddNoteInput = {
  content: Scalars['String']['input'];
  notable_id: Scalars['ULID']['input'];
};

/** Representation agent details */
export type Agent = {
  __typename?: 'Agent';
  /** Agency name */
  agency_name?: Maybe<Scalars['String']['output']>;
  /** Commission rate in basis points */
  commission_rate?: Maybe<Scalars['Int']['output']>;
  /** The contact record for this agent */
  contact?: Maybe<Contact>;
  /** Contract end date */
  contract_end?: Maybe<Scalars['Timestamp']['output']>;
  /** Contract start date */
  contract_start?: Maybe<Scalars['Timestamp']['output']>;
  /** When the agent was created */
  created_at: Scalars['Timestamp']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Whether this agent is exclusive */
  is_exclusive: Scalars['Boolean']['output'];
  /** Jobs associated with this agent (paginated, filterable, sortable) */
  jobs: JobPaginator;
  /** Territories covered by the agent */
  territories?: Maybe<Array<Scalars['String']['output']>>;
  /** When the agent was last updated */
  updated_at: Scalars['Timestamp']['output'];
};


/** Representation agent details */
export type AgentJobsArgs = {
  first?: Scalars['Int']['input'];
  orderBy?: InputMaybe<Array<AgentJobsOrderByOrderByClause>>;
  page?: InputMaybe<Scalars['Int']['input']>;
  where?: InputMaybe<AgentJobsWhereWhereConditions>;
};

/** Allowed column names for Agent.jobs.orderBy. */
export enum AgentJobsOrderByColumn {
  CreatedAt = 'CREATED_AT',
  DeliveryDeadline = 'DELIVERY_DEADLINE',
  ProjectTitle = 'PROJECT_TITLE',
  SessionDate = 'SESSION_DATE',
  Status = 'STATUS',
  UpdatedAt = 'UPDATED_AT'
}

/** Order by clause for Agent.jobs.orderBy. */
export type AgentJobsOrderByOrderByClause = {
  /** The column that is used for ordering. */
  column: AgentJobsOrderByColumn;
  /** The direction that is used for ordering. */
  order: SortOrder;
};

/** Allowed column names for Agent.jobs.where. */
export enum AgentJobsWhereColumn {
  BrandName = 'BRAND_NAME',
  Category = 'CATEGORY',
  CreatedAt = 'CREATED_AT',
  DeliveryDeadline = 'DELIVERY_DEADLINE',
  ProjectTitle = 'PROJECT_TITLE',
  SessionDate = 'SESSION_DATE',
  Status = 'STATUS',
  UpdatedAt = 'UPDATED_AT'
}

/** Dynamic WHERE conditions for the `where` argument of the query `jobs`. */
export type AgentJobsWhereWhereConditions = {
  /** A set of conditions that requires all conditions to match. */
  AND?: InputMaybe<Array<AgentJobsWhereWhereConditions>>;
  /** Check whether a relation exists. Extra conditions or a minimum amount can be applied. */
  HAS?: InputMaybe<AgentJobsWhereWhereConditionsRelation>;
  /** A set of conditions that requires at least one condition to match. */
  OR?: InputMaybe<Array<AgentJobsWhereWhereConditions>>;
  /** The column that is used for the condition. */
  column?: InputMaybe<AgentJobsWhereColumn>;
  /** The operator that is used for the condition. */
  operator?: InputMaybe<SqlOperator>;
  /** The value that is used for the condition. */
  value?: InputMaybe<Scalars['Mixed']['input']>;
};

/** Dynamic WHERE HAS conditions for the `where` argument of the query `jobs`. */
export type AgentJobsWhereWhereConditionsHasCondition = {
  /** A set of conditions that requires all conditions to match. */
  AND?: InputMaybe<Array<AgentJobsWhereWhereConditionsHasCondition>>;
  /** Check whether a relation exists. Extra conditions or a minimum amount can be applied. */
  HAS?: InputMaybe<AgentJobsWhereWhereConditionsRelation>;
  /** A set of conditions that requires at least one condition to match. */
  OR?: InputMaybe<Array<AgentJobsWhereWhereConditionsHasCondition>>;
  /** The column that is used for the condition. */
  column?: InputMaybe<Scalars['String']['input']>;
  /** The operator that is used for the condition. */
  operator?: InputMaybe<SqlOperator>;
  /** The value that is used for the condition. */
  value?: InputMaybe<Scalars['Mixed']['input']>;
};

/** Dynamic HAS conditions for WHERE conditions for the `where` argument of the query `jobs`. */
export type AgentJobsWhereWhereConditionsRelation = {
  /** The amount to test. */
  amount?: InputMaybe<Scalars['Int']['input']>;
  /** Additional condition logic. */
  condition?: InputMaybe<AgentJobsWhereWhereConditionsHasCondition>;
  /** The comparison operator to test against the amount. */
  operator?: InputMaybe<SqlOperator>;
  /** The relation that is checked. */
  relation: Scalars['String']['input'];
};

/** A paginated list of Agent items. */
export type AgentPaginator = {
  __typename?: 'AgentPaginator';
  /** A list of Agent items. */
  data: Array<Agent>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Input for archiving an activity */
export type ArchiveActivityInput = {
  id: Scalars['ULID']['input'];
};

/** Entities that can have attachments */
export type AttachableEntity = Audition | Contact | Expense | Invoice | Job;

/** File attachment */
export type Attachment = {
  __typename?: 'Attachment';
  /** The attached entity */
  attachable?: Maybe<AttachableEntity>;
  /** ID of the attached entity matching attachable_type. */
  attachable_id: Scalars['ULID']['output'];
  /** Polymorphic type discriminator for the attached entity (see AttachableEntity). */
  attachable_type: Scalars['String']['output'];
  /** Category/type of attachment */
  category: AttachmentCategory;
  /** When the attachment was created */
  created_at: Scalars['Timestamp']['output'];
  /** Storage disk */
  disk: Scalars['String']['output'];
  /** Stored filename */
  filename: Scalars['String']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Metadata payload */
  metadata?: Maybe<Scalars['JSON']['output']>;
  /** MIME type */
  mime_type: Scalars['String']['output'];
  /** Original filename */
  original_filename: Scalars['String']['output'];
  /** Storage path */
  path: Scalars['String']['output'];
  /** File size in bytes */
  size: Scalars['Int']['output'];
  /** When the attachment was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this attachment */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};

/** Attachment category enum */
export enum AttachmentCategory {
  Agreement = 'AGREEMENT',
  Contract = 'CONTRACT',
  Deliverable = 'DELIVERABLE',
  Headshot = 'HEADSHOT',
  InvoicePdf = 'INVOICE_PDF',
  Other = 'OTHER',
  Receipt = 'RECEIPT',
  Recording = 'RECORDING',
  Script = 'SCRIPT'
}

/** A paginated list of Attachment items. */
export type AttachmentPaginator = {
  __typename?: 'AttachmentPaginator';
  /** A list of Attachment items. */
  data: Array<Attachment>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Voice over audition */
export type Audition = {
  __typename?: 'Audition';
  /** Attachments for this audition */
  attachments: Array<Attachment>;
  /** Brand name */
  brand_name?: Maybe<Scalars['String']['output']>;
  /** Budget maximum (cents) */
  budget_max?: Maybe<Scalars['Int']['output']>;
  /** Budget minimum (cents) */
  budget_min?: Maybe<Scalars['Int']['output']>;
  /** Category */
  category: ProjectCategory;
  /** Character name */
  character_name?: Maybe<Scalars['String']['output']>;
  /** When the audition was created */
  created_at: Scalars['Timestamp']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Job created from this audition */
  job?: Maybe<Job>;
  /** Notes for this audition (paginated) */
  notes: NotePaginator;
  /** Project deadline */
  project_deadline?: Maybe<Scalars['Timestamp']['output']>;
  /** Project title */
  project_title: Scalars['String']['output'];
  /** Quoted rate (cents) */
  quoted_rate?: Maybe<Scalars['Int']['output']>;
  /** Rate type */
  rate_type: RateType;
  /** Response deadline */
  response_deadline?: Maybe<Scalars['Timestamp']['output']>;
  /** External source reference */
  source_reference?: Maybe<Scalars['String']['output']>;
  /** The source entity (platform or contact) */
  sourceable?: Maybe<SourceableEntity>;
  /** ID of the source entity matching sourceable_type. */
  sourceable_id: Scalars['ULID']['output'];
  /** Polymorphic type discriminator for the source entity (see SourceableEntity). */
  sourceable_type: Scalars['String']['output'];
  /** Audition status */
  status: AuditionStatus;
  /** When the audition was submitted to the client */
  submitted_at?: Maybe<Scalars['Timestamp']['output']>;
  /** When the audition was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** Usage rights associated with this audition */
  usageRights: Array<UsageRight>;
  /** The user who owns this audition */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
  /** Word count */
  word_count?: Maybe<Scalars['Int']['output']>;
};


/** Voice over audition */
export type AuditionNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

export type AuditionChartResponse = ChartResponse & {
  __typename?: 'AuditionChartResponse';
  /** Chart data points with timestamp and count of auditions submitted */
  chart: Array<ChartPoint>;
  /**
   * The actual time window used for the chart.
   * Early MTD/QTD/YTD requests are automatically expanded for better visualization.
   */
  effectiveWindow: DateRangeWindow;
  /** The range of data requested - either a compact range (1W, MTD, etc.) or explicit dates */
  range: ChartRange;
};

export type AuditionMetrics = {
  __typename?: 'AuditionMetrics';
  /**
   * Booking rate: percentage of WON auditions vs total auditions in the period.
   * Returns 0.0 if no auditions exist.
   */
  booking_rate: Scalars['Float']['output'];
  /** Auditions submitted within the requested period. */
  current: AuditionMetricsSnapshot;
};

export type AuditionMetricsResponse = {
  __typename?: 'AuditionMetricsResponse';
  /** Current audition metrics including counts and booking rate. */
  metrics: AuditionMetrics;
  /** The exact period boundaries used for metrics calculation (no expansion) */
  period: DateRange;
};

export type AuditionMetricsSnapshot = {
  __typename?: 'AuditionMetricsSnapshot';
  /** Total number of auditions for the comparison period immediately preceding the current range. */
  comparison_total: Scalars['Int']['output'];
  /** Total number of auditions submitted during the period. */
  total: Scalars['Int']['output'];
  /** Percentage change vs the comparison period's total. */
  trend_percentage: Scalars['Float']['output'];
};

/** A paginated list of Audition items. */
export type AuditionPaginator = {
  __typename?: 'AuditionPaginator';
  /** A list of Audition items. */
  data: Array<Audition>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Audition status enum */
export enum AuditionStatus {
  Callback = 'CALLBACK',
  Expired = 'EXPIRED',
  Lost = 'LOST',
  Preparing = 'PREPARING',
  Received = 'RECEIVED',
  Shortlisted = 'SHORTLISTED',
  Submitted = 'SUBMITTED',
  Won = 'WON'
}

/** Authentication mode for code-based login. */
export enum AuthMode {
  /** Establish a server session for the client. */
  Session = 'SESSION',
  /** Return a bearer token in the response. */
  Token = 'TOKEN'
}

/** Error response for code verification. */
export type AuthenticateWithCodeErrorResponse = {
  __typename?: 'AuthenticateWithCodeErrorResponse';
  /** Human-readable error message. */
  message: Scalars['String']['output'];
};

/** Input for verifying an authentication code. */
export type AuthenticateWithCodeInput = {
  /** 6-digit numeric authentication code. */
  code: Scalars['String']['input'];
  /** Optional device label for token/session creation. */
  device_name?: InputMaybe<Scalars['String']['input']>;
  /** Email address the code was sent to. */
  email: Scalars['String']['input'];
  /** Auth mode that determines token vs session behavior. */
  mode: AuthMode;
};

/** Success response for code verification in session mode. */
export type AuthenticateWithCodeOkResponse = {
  __typename?: 'AuthenticateWithCodeOkResponse';
  /** True when the request succeeded. */
  ok: Scalars['Boolean']['output'];
};

/** Union response for code verification. */
export type AuthenticateWithCodeResponse = AuthenticateWithCodeErrorResponse | AuthenticateWithCodeOkResponse | AuthenticateWithCodeTokenResponse;

/** Success response for code verification in token mode. */
export type AuthenticateWithCodeTokenResponse = {
  __typename?: 'AuthenticateWithCodeTokenResponse';
  /** Bearer token for API authentication. */
  token: Scalars['String']['output'];
};

/** Business profile used for invoices */
export type BusinessProfile = {
  __typename?: 'BusinessProfile';
  /** City */
  address_city?: Maybe<Scalars['String']['output']>;
  /** Country */
  address_country?: Maybe<Scalars['String']['output']>;
  /** Postal code */
  address_postal?: Maybe<Scalars['String']['output']>;
  /** State/Region */
  address_state?: Maybe<Scalars['String']['output']>;
  /** Street address */
  address_street?: Maybe<Scalars['String']['output']>;
  /** Business name */
  business_name?: Maybe<Scalars['String']['output']>;
  /** When the profile was created */
  created_at: Scalars['Timestamp']['output'];
  /** Email address */
  email?: Maybe<Scalars['String']['output']>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Logo path */
  logo_path?: Maybe<Scalars['String']['output']>;
  /** Payment instructions */
  payment_instructions?: Maybe<Scalars['String']['output']>;
  /** Phone number */
  phone?: Maybe<Scalars['String']['output']>;
  /** When the profile was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this profile */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};

/** A paginated list of BusinessProfile items. */
export type BusinessProfilePaginator = {
  __typename?: 'BusinessProfilePaginator';
  /** A list of BusinessProfile items. */
  data: Array<BusinessProfile>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Generic chart point with timestamp and value - alias for TimeSeriesDataPoint */
export type ChartPoint = {
  __typename?: 'ChartPoint';
  /** UTC timestamp in milliseconds representing the date/time for this data point */
  timestamp: Scalars['Timestamp']['output'];
  /** Value at this point in time (typically in cents for monetary values) */
  value: Scalars['Int']['output'];
};

/** Union type for chart range - can be either a compact range enum or explicit date range */
export type ChartRange = CompactRangeValue | DateRange;

/** Common interface for all chart responses with time-series data */
export type ChartResponse = {
  /** Chart data points with timestamp and value */
  chart: Array<ChartPoint>;
  /**
   * The actual time window used for the chart.
   * Early MTD/QTD/YTD requests are automatically expanded for better visualization.
   */
  effectiveWindow: DateRangeWindow;
  /** The range of data requested - either a compact range (1W, MTD, etc.) or explicit dates */
  range: ChartRange;
};

/** Client details */
export type Client = {
  __typename?: 'Client';
  /** The contact record for this client */
  contact?: Maybe<Contact>;
  /** When the client was created */
  created_at: Scalars['Timestamp']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Industry */
  industry?: Maybe<Scalars['String']['output']>;
  /** Payment terms */
  payment_terms?: Maybe<Scalars['String']['output']>;
  /** Client type */
  type: ClientType;
  /** When the client was last updated */
  updated_at: Scalars['Timestamp']['output'];
};

/** A paginated list of Client items. */
export type ClientPaginator = {
  __typename?: 'ClientPaginator';
  /** A list of Client items. */
  data: Array<Client>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Client type enum */
export enum ClientType {
  /** Company */
  Company = 'COMPANY',
  /** Individual */
  Individual = 'INDIVIDUAL'
}

/** Compact predefined ranges for charting and metrics, etc. */
export enum CompactRange {
  AllTime = 'AllTime',
  FourWeeks = 'FourWeeks',
  MonthToDate = 'MonthToDate',
  OneWeek = 'OneWeek',
  OneYear = 'OneYear',
  QuarterToDate = 'QuarterToDate',
  YearToDate = 'YearToDate'
}

/**
 * Wrapper type for CompactRange enum values.
 * Used in union types where both enum and complex types need to be supported.
 */
export type CompactRangeValue = {
  __typename?: 'CompactRangeValue';
  value: CompactRange;
};

/** Contact record */
export type Contact = {
  __typename?: 'Contact';
  /** City */
  address_city?: Maybe<Scalars['String']['output']>;
  /** Country */
  address_country?: Maybe<Scalars['String']['output']>;
  /** Postal code */
  address_postal?: Maybe<Scalars['String']['output']>;
  /** State/Region */
  address_state?: Maybe<Scalars['String']['output']>;
  /** Street address */
  address_street?: Maybe<Scalars['String']['output']>;
  /** Attachments for this contact */
  attachments: Array<Attachment>;
  /** The related contactable entity */
  contactable?: Maybe<ContactableEntity>;
  /** ID of the related entity matching contactable_type. */
  contactable_id: Scalars['ULID']['output'];
  /** Polymorphic type discriminator for the related entity (see ContactableEntity). */
  contactable_type: Scalars['String']['output'];
  /** When the contact was created */
  created_at: Scalars['Timestamp']['output'];
  /** Email */
  email?: Maybe<Scalars['String']['output']>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Last contacted timestamp */
  last_contacted_at?: Maybe<Scalars['Timestamp']['output']>;
  /** Contact name */
  name: Scalars['String']['output'];
  /** Notes for this entity (paginated) */
  notes: NotePaginator;
  /** Phone */
  phone?: Maybe<Scalars['String']['output']>;
  /** Phone extension */
  phone_ext?: Maybe<Scalars['String']['output']>;
  /** When the contact was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this contact */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};


/** Contact record */
export type ContactNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** A paginated list of Contact items. */
export type ContactPaginator = {
  __typename?: 'ContactPaginator';
  /** A list of Contact items. */
  data: Array<Contact>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Entities that can be assigned to a contact */
export type ContactableEntity = Agent | Client;

/** Input for creating an agent contact in one mutation */
export type CreateAgentContactInput = {
  /** City */
  address_city?: InputMaybe<Scalars['String']['input']>;
  /** Country */
  address_country?: InputMaybe<Scalars['String']['input']>;
  /** Postal code */
  address_postal?: InputMaybe<Scalars['String']['input']>;
  /** State/Region */
  address_state?: InputMaybe<Scalars['String']['input']>;
  /** Street address */
  address_street?: InputMaybe<Scalars['String']['input']>;
  /** Email */
  email?: InputMaybe<Scalars['String']['input']>;
  /** Last contacted timestamp */
  last_contacted_at?: InputMaybe<Scalars['Timestamp']['input']>;
  /** Contact name */
  name: Scalars['String']['input'];
  /** Phone */
  phone?: InputMaybe<Scalars['String']['input']>;
  /** Phone extension */
  phone_ext?: InputMaybe<Scalars['String']['input']>;
};

/** Input for creating an agent and primary contact in one mutation */
export type CreateAgentWithContactInput = {
  /** Agency name */
  agency_name?: InputMaybe<Scalars['String']['input']>;
  /** Commission rate in basis points */
  commission_rate?: InputMaybe<Scalars['Int']['input']>;
  /** Contact data */
  contact: CreateAgentContactInput;
  /** Contract end date */
  contract_end?: InputMaybe<Scalars['Timestamp']['input']>;
  /** Contract start date */
  contract_start?: InputMaybe<Scalars['Timestamp']['input']>;
  /** Whether this agent is exclusive */
  is_exclusive?: InputMaybe<Scalars['Boolean']['input']>;
  /** Territories covered by the agent */
  territories?: InputMaybe<Array<Scalars['String']['input']>>;
};

/** Input for creating an attachment */
export type CreateAttachmentInput = {
  /** ID of the attached entity matching attachable_type. */
  attachable_id: Scalars['ULID']['input'];
  /** Polymorphic type discriminator for the attached entity (see AttachableEntity). */
  attachable_type: Scalars['String']['input'];
  category: AttachmentCategory;
  disk: Scalars['String']['input'];
  filename: Scalars['String']['input'];
  metadata?: InputMaybe<Scalars['JSON']['input']>;
  mime_type: Scalars['String']['input'];
  original_filename: Scalars['String']['input'];
  path: Scalars['String']['input'];
  size: Scalars['Int']['input'];
};

/** Input for creating an audition */
export type CreateAuditionInput = {
  brand_name?: InputMaybe<Scalars['String']['input']>;
  budget_max?: InputMaybe<Scalars['Int']['input']>;
  budget_min?: InputMaybe<Scalars['Int']['input']>;
  category: ProjectCategory;
  character_name?: InputMaybe<Scalars['String']['input']>;
  project_deadline?: InputMaybe<Scalars['Timestamp']['input']>;
  project_title: Scalars['String']['input'];
  quoted_rate?: InputMaybe<Scalars['Int']['input']>;
  rate_type: RateType;
  response_deadline?: InputMaybe<Scalars['Timestamp']['input']>;
  source_reference?: InputMaybe<Scalars['String']['input']>;
  /** ID of the source entity matching sourceable_type. */
  sourceable_id: Scalars['ULID']['input'];
  /** Polymorphic type discriminator for the source entity (see SourceableEntity). */
  sourceable_type: Scalars['String']['input'];
  status: AuditionStatus;
  submitted_at?: InputMaybe<Scalars['Timestamp']['input']>;
  word_count?: InputMaybe<Scalars['Int']['input']>;
};

/** Input for creating a client */
export type CreateClientInput = {
  industry?: InputMaybe<Scalars['String']['input']>;
  payment_terms?: InputMaybe<Scalars['String']['input']>;
  type: ClientType;
};

/** Input for creating a contact */
export type CreateContactInput = {
  address_city?: InputMaybe<Scalars['String']['input']>;
  address_country?: InputMaybe<Scalars['String']['input']>;
  address_postal?: InputMaybe<Scalars['String']['input']>;
  address_state?: InputMaybe<Scalars['String']['input']>;
  address_street?: InputMaybe<Scalars['String']['input']>;
  /** ID of the related entity matching contactable_type. */
  contactable_id: Scalars['ULID']['input'];
  /** Polymorphic type discriminator for the related entity (see ContactableEntity). */
  contactable_type: Scalars['String']['input'];
  email?: InputMaybe<Scalars['String']['input']>;
  last_contacted_at?: InputMaybe<Scalars['Timestamp']['input']>;
  name: Scalars['String']['input'];
  phone?: InputMaybe<Scalars['String']['input']>;
  phone_ext?: InputMaybe<Scalars['String']['input']>;
};

/** Input for creating an expense definition */
export type CreateExpenseDefinitionInput = {
  amount: MoneyInput;
  category: ExpenseCategory;
  ends_at?: InputMaybe<Scalars['Timestamp']['input']>;
  is_active?: InputMaybe<Scalars['Boolean']['input']>;
  name: Scalars['String']['input'];
  recurrence: ExpenseRecurrence;
  recurrence_day?: InputMaybe<Scalars['Int']['input']>;
  starts_at: Scalars['Timestamp']['input'];
};

/** Input for creating an expense */
export type CreateExpenseInput = {
  amount: MoneyInput;
  category: ExpenseCategory;
  date: Scalars['Timestamp']['input'];
  description: Scalars['String']['input'];
  expense_definition_id?: InputMaybe<Scalars['ULID']['input']>;
};

/** Input for creating an invoice */
export type CreateInvoiceInput = {
  client_id: Scalars['ULID']['input'];
  due_at: Scalars['Timestamp']['input'];
  invoice_number: Scalars['String']['input'];
  issued_at: Scalars['Timestamp']['input'];
  job_id?: InputMaybe<Scalars['ULID']['input']>;
  paid_at?: InputMaybe<Scalars['Timestamp']['input']>;
  status: InvoiceStatus;
  subtotal: MoneyInput;
  tax_amount?: InputMaybe<MoneyInput>;
  tax_rate?: InputMaybe<Scalars['Float']['input']>;
  total: MoneyInput;
};

/** Input for creating an invoice item */
export type CreateInvoiceItemInput = {
  amount: Scalars['Int']['input'];
  description: Scalars['String']['input'];
  invoice_id: Scalars['ULID']['input'];
  quantity: Scalars['Float']['input'];
  unit_price: Scalars['Int']['input'];
};

/** Input for creating an agent relationship during job creation */
export type CreateJobAgentCreateInput = {
  contact: CreateJobContactInput;
};

/** Input for agent data used in nested relationship creation */
export type CreateJobAgentInput = {
  agency_name?: InputMaybe<Scalars['String']['input']>;
  commission_rate?: InputMaybe<Scalars['Int']['input']>;
  contract_end?: InputMaybe<Scalars['Timestamp']['input']>;
  contract_start?: InputMaybe<Scalars['Timestamp']['input']>;
  is_exclusive?: InputMaybe<Scalars['Boolean']['input']>;
  territories?: InputMaybe<Array<Scalars['String']['input']>>;
};

/** Input for resolving the agent relationship during job creation */
export type CreateJobAgentRelationInput = {
  create?: InputMaybe<CreateJobAgentCreateInput>;
  id?: InputMaybe<Scalars['ULID']['input']>;
};

/** Input for linking an audition during job creation */
export type CreateJobAuditionRelationInput = {
  id: Scalars['ULID']['input'];
};

/** Input for creating a client relationship during job creation */
export type CreateJobClientCreateInput = {
  contact: CreateJobContactInput;
};

/** Input for resolving the client relationship during job creation */
export type CreateJobClientRelationInput = {
  create?: InputMaybe<CreateJobClientCreateInput>;
  id?: InputMaybe<Scalars['ULID']['input']>;
};

/** Input for contact data used in nested relationship creation */
export type CreateJobContactInput = {
  address_city?: InputMaybe<Scalars['String']['input']>;
  address_country?: InputMaybe<Scalars['String']['input']>;
  address_postal?: InputMaybe<Scalars['String']['input']>;
  address_state?: InputMaybe<Scalars['String']['input']>;
  address_street?: InputMaybe<Scalars['String']['input']>;
  contactable: CreateJobContactableInput;
  email?: InputMaybe<Scalars['String']['input']>;
  last_contacted_at?: InputMaybe<Scalars['Timestamp']['input']>;
  name: Scalars['String']['input'];
  phone?: InputMaybe<Scalars['String']['input']>;
  phone_ext?: InputMaybe<Scalars['String']['input']>;
};

/** Input for contactable data used in nested relationship creation */
export type CreateJobContactableInput = {
  agent?: InputMaybe<CreateJobAgentInput>;
  client?: InputMaybe<CreateClientInput>;
};

/** Input for creating a job */
export type CreateJobInput = {
  actual_hours?: InputMaybe<Scalars['Float']['input']>;
  agent?: InputMaybe<CreateJobAgentRelationInput>;
  audition?: InputMaybe<CreateJobAuditionRelationInput>;
  brand_name?: InputMaybe<Scalars['String']['input']>;
  category: ProjectCategory;
  character_name?: InputMaybe<Scalars['String']['input']>;
  client: CreateJobClientRelationInput;
  contracted_rate: MoneyInput;
  delivered_at?: InputMaybe<Scalars['Timestamp']['input']>;
  delivery_deadline?: InputMaybe<Scalars['Timestamp']['input']>;
  estimated_hours?: InputMaybe<Scalars['Float']['input']>;
  project_title: Scalars['String']['input'];
  rate_type: RateType;
  session_date?: InputMaybe<Scalars['Timestamp']['input']>;
  status: JobStatus;
  word_count?: InputMaybe<Scalars['Int']['input']>;
};

/** Input for creating a platform */
export type CreatePlatformInput = {
  external_id?: InputMaybe<Scalars['String']['input']>;
  name: Scalars['String']['input'];
  url?: InputMaybe<Scalars['String']['input']>;
  username?: InputMaybe<Scalars['String']['input']>;
};

/** Input for creating usage rights */
export type CreateUsageRightInput = {
  ai_rights_granted?: InputMaybe<Scalars['Boolean']['input']>;
  duration_months?: InputMaybe<Scalars['Int']['input']>;
  duration_type: DurationType;
  exclusivity?: InputMaybe<Scalars['Boolean']['input']>;
  exclusivity_category?: InputMaybe<Scalars['String']['input']>;
  expiration_date?: InputMaybe<Scalars['Timestamp']['input']>;
  geographic_scope: GeographicScope;
  media_types: Array<UsageMediaType>;
  renewal_reminder_sent?: InputMaybe<Scalars['Boolean']['input']>;
  start_date?: InputMaybe<Scalars['Timestamp']['input']>;
  type: UsageType;
  /** ID of the usable entity matching usable_type. */
  usable_id: Scalars['ULID']['input'];
  /** Polymorphic type discriminator for the usable entity (see UsableEntity). */
  usable_type: Scalars['String']['input'];
};

/** A date range with start and end timestamps */
export type DateRange = {
  __typename?: 'DateRange';
  /** End date/time in UTC milliseconds */
  end: Scalars['Timestamp']['output'];
  /** Start date/time in UTC milliseconds */
  start: Scalars['Timestamp']['output'];
};

/** Describes the actual date range used for chart data */
export type DateRangeWindow = {
  __typename?: 'DateRangeWindow';
  daysInRange: Scalars['Int']['output'];
  end: Scalars['Timestamp']['output'];
  expansionReason?: Maybe<Scalars['String']['output']>;
  start: Scalars['Timestamp']['output'];
  wasExpanded: Scalars['Boolean']['output'];
};

/** Duration type enum */
export enum DurationType {
  Fixed = 'FIXED',
  Perpetual = 'PERPETUAL'
}

/** Expense record */
export type Expense = {
  __typename?: 'Expense';
  /** Monetary amount with original and converted values */
  amount: MonetaryAmount;
  /** Attachments for this expense */
  attachments: Array<Attachment>;
  /** Category */
  category: ExpenseCategory;
  /** When the expense was created */
  created_at: Scalars['Timestamp']['output'];
  /** Expense date */
  date: Scalars['Timestamp']['output'];
  /** Description */
  description: Scalars['String']['output'];
  /** The expense definition */
  expenseDefinition?: Maybe<ExpenseDefinition>;
  /** Expense definition ID */
  expense_definition_id?: Maybe<Scalars['ULID']['output']>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Notes for this entity (paginated) */
  notes: NotePaginator;
  /** When the expense was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this expense */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};


/** Expense record */
export type ExpenseNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** Expense category enum */
export enum ExpenseCategory {
  Equipment = 'EQUIPMENT',
  Marketing = 'MARKETING',
  Membership = 'MEMBERSHIP',
  Office = 'OFFICE',
  Other = 'OTHER',
  ProfessionalServices = 'PROFESSIONAL_SERVICES',
  Software = 'SOFTWARE',
  Studio = 'STUDIO',
  Training = 'TRAINING',
  Travel = 'TRAVEL'
}

/** Expense definition (recurring or template) */
export type ExpenseDefinition = {
  __typename?: 'ExpenseDefinition';
  /** Template amount */
  amount: MonetaryAmount;
  /** Category */
  category: ExpenseCategory;
  /** When the definition was created */
  created_at: Scalars['Timestamp']['output'];
  /** End date */
  ends_at?: Maybe<Scalars['Timestamp']['output']>;
  /** Expenses created from this definition */
  expenses: Array<Expense>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Is active */
  is_active: Scalars['Boolean']['output'];
  /** Name */
  name: Scalars['String']['output'];
  /** Notes for this expense definition (paginated) */
  notes: NotePaginator;
  /** Recurrence */
  recurrence: ExpenseRecurrence;
  /** Recurrence day */
  recurrence_day?: Maybe<Scalars['Int']['output']>;
  /** Start date */
  starts_at: Scalars['Timestamp']['output'];
  /** When the definition was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this definition */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};


/** Expense definition (recurring or template) */
export type ExpenseDefinitionNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** A paginated list of ExpenseDefinition items. */
export type ExpenseDefinitionPaginator = {
  __typename?: 'ExpenseDefinitionPaginator';
  /** A list of ExpenseDefinition items. */
  data: Array<ExpenseDefinition>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** A paginated list of Expense items. */
export type ExpensePaginator = {
  __typename?: 'ExpensePaginator';
  /** A list of Expense items. */
  data: Array<Expense>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Expense recurrence enum */
export enum ExpenseRecurrence {
  Monthly = 'MONTHLY',
  OneOff = 'ONE_OFF',
  Weekly = 'WEEKLY',
  Yearly = 'YEARLY'
}

/** Geographic scope enum */
export enum GeographicScope {
  Local = 'LOCAL',
  MultiNational = 'MULTI_NATIONAL',
  National = 'NATIONAL',
  Regional = 'REGIONAL',
  Worldwide = 'WORLDWIDE'
}

/** Invoice for a job */
export type Invoice = {
  __typename?: 'Invoice';
  /** Attachments for this invoice */
  attachments: Array<Attachment>;
  /** The client contact */
  client: Contact;
  /** Client contact ID */
  client_id: Scalars['ULID']['output'];
  /** When the invoice was created */
  created_at: Scalars['Timestamp']['output'];
  /** Due date */
  due_at: Scalars['Timestamp']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Invoice line items */
  invoiceItems: Array<InvoiceItem>;
  /** Invoice number */
  invoice_number: Scalars['String']['output'];
  /** Issued date */
  issued_at: Scalars['Timestamp']['output'];
  /** The job this invoice belongs to */
  job?: Maybe<Job>;
  /** Job ID */
  job_id?: Maybe<Scalars['ULID']['output']>;
  /** Notes for this entity (paginated) */
  notes: NotePaginator;
  /** Paid date */
  paid_at?: Maybe<Scalars['Timestamp']['output']>;
  /** Invoice status */
  status: InvoiceStatus;
  /** Subtotal */
  subtotal: MonetaryAmount;
  /** Tax amount */
  tax_amount?: Maybe<MonetaryAmount>;
  /** Tax rate (decimal) */
  tax_rate?: Maybe<Scalars['Float']['output']>;
  /** Total */
  total: MonetaryAmount;
  /** When the invoice was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this invoice */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};


/** Invoice for a job */
export type InvoiceNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** Invoice line item */
export type InvoiceItem = {
  __typename?: 'InvoiceItem';
  /** Amount in cents */
  amount: Scalars['Int']['output'];
  /** Line amount converted to the user's base currency. */
  amount_in_base_currency: Money;
  /** When the item was created */
  created_at: Scalars['Timestamp']['output'];
  /** Description */
  description: Scalars['String']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** The invoice this item belongs to */
  invoice: Invoice;
  /** Invoice ID */
  invoice_id: Scalars['ULID']['output'];
  /** Quantity */
  quantity: Scalars['Float']['output'];
  /** Unit price in cents */
  unit_price: Scalars['Int']['output'];
  /** Unit price converted to the user's base currency. */
  unit_price_in_base_currency: Money;
  /** When the item was last updated */
  updated_at: Scalars['Timestamp']['output'];
};

/** A paginated list of InvoiceItem items. */
export type InvoiceItemPaginator = {
  __typename?: 'InvoiceItemPaginator';
  /** A list of InvoiceItem items. */
  data: Array<InvoiceItem>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** A paginated list of Invoice items. */
export type InvoicePaginator = {
  __typename?: 'InvoicePaginator';
  /** A list of Invoice items. */
  data: Array<Invoice>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Invoice status enum */
export enum InvoiceStatus {
  Cancelled = 'CANCELLED',
  Draft = 'DRAFT',
  Overdue = 'OVERDUE',
  Paid = 'PAID',
  Sent = 'SENT'
}

/** Voice over job/project */
export type Job = {
  __typename?: 'Job';
  /** Actual hours */
  actual_hours?: Maybe<Scalars['Float']['output']>;
  /** The agent contact */
  agent?: Maybe<Contact>;
  /** Agent contact ID */
  agent_id?: Maybe<Scalars['ULID']['output']>;
  /** Archive timestamp (null when active) */
  archived_at?: Maybe<Scalars['Timestamp']['output']>;
  /** Attachments for the job */
  attachments: Array<Attachment>;
  /** The audition this job came from */
  audition?: Maybe<Audition>;
  /** Audition ID */
  audition_id?: Maybe<Scalars['ULID']['output']>;
  /** Brand name */
  brand_name?: Maybe<Scalars['String']['output']>;
  /** Category */
  category: ProjectCategory;
  /** Character name */
  character_name?: Maybe<Scalars['String']['output']>;
  /** The client contact */
  client: Contact;
  /** Client contact ID */
  client_id: Scalars['ULID']['output'];
  /** Contracted rate */
  contracted_rate: MonetaryAmount;
  /** When the job was created */
  created_at: Scalars['Timestamp']['output'];
  /** Delivery timestamp */
  delivered_at?: Maybe<Scalars['Timestamp']['output']>;
  /** Delivery deadline */
  delivery_deadline?: Maybe<Scalars['Timestamp']['output']>;
  /** Estimated hours */
  estimated_hours?: Maybe<Scalars['Float']['output']>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Invoices for this job */
  invoices: Array<Invoice>;
  /** Notes for the job (paginated) */
  notes: NotePaginator;
  /** Project title */
  project_title: Scalars['String']['output'];
  /** Rate type */
  rate_type: RateType;
  /** Session date */
  session_date?: Maybe<Scalars['Timestamp']['output']>;
  /** Job status */
  status: JobStatus;
  /** When the job was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** Usage rights for the job */
  usageRights: Array<UsageRight>;
  /** The user who owns this job */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
  /** Word count */
  word_count?: Maybe<Scalars['Int']['output']>;
};


/** Voice over job/project */
export type JobNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** A paginated list of Job items. */
export type JobPaginator = {
  __typename?: 'JobPaginator';
  /** A list of Job items. */
  data: Array<Job>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Job status enum */
export enum JobStatus {
  Booked = 'BOOKED',
  Cancelled = 'CANCELLED',
  Completed = 'COMPLETED',
  Delivered = 'DELIVERED',
  InProgress = 'IN_PROGRESS',
  Revision = 'REVISION'
}

/** A monetary amount with both original and converted values */
export type MonetaryAmount = {
  __typename?: 'MonetaryAmount';
  /** Value converted to the user's base currency */
  converted: Money;
  /** Original value as stored in the database */
  original: Money;
};

/** Represents a monetary value in a specific currency */
export type Money = {
  __typename?: 'Money';
  /** The amount in cents */
  amount_cents: Scalars['Int']['output'];
  /** The currency code (e.g. USD, CAD) */
  currency: Scalars['String']['output'];
  /** Precision of the conversion: EXACT (rate found for date) or ESTIMATED (fallback rate used) */
  precision: Precision;
};

/** Input for monetary values */
export type MoneyInput = {
  /** Amount in cents */
  amount_cents: Scalars['Int']['input'];
  /** Currency code (ISO 4217) */
  currency: Scalars['String']['input'];
};

/** Root mutation type */
export type Mutation = {
  __typename?: 'Mutation';
  /** Add a note to an entity */
  addNote: Note;
  /** Archive an activity for the authenticated user */
  archiveActivity: Activity;
  /** Archive a job */
  archiveJob?: Maybe<Job>;
  /** Verify an auth code and authenticate the user */
  authenticateWithCode: AuthenticateWithCodeResponse;
  /** Create a new agent and its primary contact */
  createAgent: Agent;
  /** Create a new attachment */
  createAttachment: Attachment;
  /** Create a new audition */
  createAudition: Audition;
  /** Create a new client */
  createClient: Client;
  /** Create a new contact */
  createContact: Contact;
  /** Create a new expense */
  createExpense: Expense;
  /** Create a new expense definition */
  createExpenseDefinition: ExpenseDefinition;
  /** Create a new invoice */
  createInvoice: Invoice;
  /** Create a new invoice item */
  createInvoiceItem: InvoiceItem;
  /** Create a new job */
  createJob: Job;
  /** Create a new platform */
  createPlatform: Platform;
  /** Create a new usage right */
  createUsageRight: UsageRight;
  /** Delete an agent */
  deleteAgent?: Maybe<Agent>;
  /** Delete an attachment */
  deleteAttachment?: Maybe<Attachment>;
  /** Delete an audition */
  deleteAudition?: Maybe<Audition>;
  /** Delete a client */
  deleteClient?: Maybe<Client>;
  /** Delete a contact */
  deleteContact?: Maybe<Contact>;
  /** Delete an expense */
  deleteExpense?: Maybe<Expense>;
  /** Delete an expense definition */
  deleteExpenseDefinition?: Maybe<ExpenseDefinition>;
  /** Delete an invoice */
  deleteInvoice?: Maybe<Invoice>;
  /** Delete an invoice item */
  deleteInvoiceItem?: Maybe<InvoiceItem>;
  /** Delete a job */
  deleteJob?: Maybe<Job>;
  /** Delete a note */
  deleteNote?: Maybe<Note>;
  /** Delete a platform */
  deletePlatform?: Maybe<Platform>;
  /** Delete a usage right */
  deleteUsageRight?: Maybe<UsageRight>;
  /** Request an auth code for an existing user */
  requestAuthenticationCode: RequestAuthenticationCodeResponse;
  /** Revoke the current token/session */
  revokeToken: RevokeTokenResponse;
  /** Register a new user and send an auth code */
  signUpAndRequestAuthenticationToken: SignUpAndRequestAuthenticationTokenResponse;
  /** Snooze an activity for the authenticated user */
  snoozeActivity: Activity;
  /** Unarchive a job */
  unarchiveJob?: Maybe<Job>;
  /** Update an agent and/or its primary contact */
  updateAgent?: Maybe<Agent>;
  /** Update an attachment */
  updateAttachment?: Maybe<Attachment>;
  /** Update an audition */
  updateAudition?: Maybe<Audition>;
  /** Update a business profile */
  updateBusinessProfile?: Maybe<BusinessProfile>;
  /** Update a client */
  updateClient?: Maybe<Client>;
  /** Update a contact */
  updateContact?: Maybe<Contact>;
  /** Update an expense */
  updateExpense?: Maybe<Expense>;
  /** Update an expense definition */
  updateExpenseDefinition?: Maybe<ExpenseDefinition>;
  /** Update an invoice */
  updateInvoice?: Maybe<Invoice>;
  /** Update an invoice item */
  updateInvoiceItem?: Maybe<InvoiceItem>;
  /** Update a job */
  updateJob?: Maybe<Job>;
  /** Update the authenticated user's profile */
  updateMe: User;
  /** Update the authenticated user's settings */
  updateMySettings: Settings;
  /** Update a note */
  updateNote?: Maybe<Note>;
  /** Update a platform */
  updatePlatform?: Maybe<Platform>;
  /** Update a usage right */
  updateUsageRight?: Maybe<UsageRight>;
};


/** Root mutation type */
export type MutationAddNoteArgs = {
  input: AddNoteInput;
};


/** Root mutation type */
export type MutationArchiveActivityArgs = {
  input: ArchiveActivityInput;
};


/** Root mutation type */
export type MutationArchiveJobArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationAuthenticateWithCodeArgs = {
  input: AuthenticateWithCodeInput;
};


/** Root mutation type */
export type MutationCreateAgentArgs = {
  input: CreateAgentWithContactInput;
};


/** Root mutation type */
export type MutationCreateAttachmentArgs = {
  input: CreateAttachmentInput;
};


/** Root mutation type */
export type MutationCreateAuditionArgs = {
  input: CreateAuditionInput;
};


/** Root mutation type */
export type MutationCreateClientArgs = {
  input: CreateClientInput;
};


/** Root mutation type */
export type MutationCreateContactArgs = {
  input: CreateContactInput;
};


/** Root mutation type */
export type MutationCreateExpenseArgs = {
  input: CreateExpenseInput;
};


/** Root mutation type */
export type MutationCreateExpenseDefinitionArgs = {
  input: CreateExpenseDefinitionInput;
};


/** Root mutation type */
export type MutationCreateInvoiceArgs = {
  input: CreateInvoiceInput;
};


/** Root mutation type */
export type MutationCreateInvoiceItemArgs = {
  input: CreateInvoiceItemInput;
};


/** Root mutation type */
export type MutationCreateJobArgs = {
  input: CreateJobInput;
};


/** Root mutation type */
export type MutationCreatePlatformArgs = {
  input: CreatePlatformInput;
};


/** Root mutation type */
export type MutationCreateUsageRightArgs = {
  input: CreateUsageRightInput;
};


/** Root mutation type */
export type MutationDeleteAgentArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteAttachmentArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteAuditionArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteClientArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteContactArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteExpenseArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteExpenseDefinitionArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteInvoiceArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteInvoiceItemArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteJobArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteNoteArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeletePlatformArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationDeleteUsageRightArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationRequestAuthenticationCodeArgs = {
  input: RequestAuthenticationCodeInput;
};


/** Root mutation type */
export type MutationSignUpAndRequestAuthenticationTokenArgs = {
  input: SignUpAndRequestAuthenticationTokenInput;
};


/** Root mutation type */
export type MutationSnoozeActivityArgs = {
  input: SnoozeActivityInput;
};


/** Root mutation type */
export type MutationUnarchiveJobArgs = {
  id: Scalars['ULID']['input'];
};


/** Root mutation type */
export type MutationUpdateAgentArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateAgentWithContactInput;
};


/** Root mutation type */
export type MutationUpdateAttachmentArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateAttachmentInput;
};


/** Root mutation type */
export type MutationUpdateAuditionArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateAuditionInput;
};


/** Root mutation type */
export type MutationUpdateBusinessProfileArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateBusinessProfileInput;
};


/** Root mutation type */
export type MutationUpdateClientArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateClientInput;
};


/** Root mutation type */
export type MutationUpdateContactArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateContactInput;
};


/** Root mutation type */
export type MutationUpdateExpenseArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateExpenseInput;
};


/** Root mutation type */
export type MutationUpdateExpenseDefinitionArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateExpenseDefinitionInput;
};


/** Root mutation type */
export type MutationUpdateInvoiceArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateInvoiceInput;
};


/** Root mutation type */
export type MutationUpdateInvoiceItemArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateInvoiceItemInput;
};


/** Root mutation type */
export type MutationUpdateJobArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateJobInput;
};


/** Root mutation type */
export type MutationUpdateMeArgs = {
  input: UpdateUserInput;
};


/** Root mutation type */
export type MutationUpdateMySettingsArgs = {
  input: UpdateSettingsInput;
};


/** Root mutation type */
export type MutationUpdateNoteArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateNoteInput;
};


/** Root mutation type */
export type MutationUpdatePlatformArgs = {
  id: Scalars['ULID']['input'];
  input: UpdatePlatformInput;
};


/** Root mutation type */
export type MutationUpdateUsageRightArgs = {
  id: Scalars['ULID']['input'];
  input: UpdateUsageRightInput;
};

/** Entities that can have notes */
export type NotableEntity = Activity | Audition | Contact | Expense | ExpenseDefinition | Invoice | Job | Platform | UsageRight;

/** User note attached to an entity */
export type Note = {
  __typename?: 'Note';
  /** Content of the note */
  content: Scalars['String']['output'];
  /** When the note was created */
  created_at: Scalars['Timestamp']['output'];
  /** When the note was soft deleted */
  deleted_at?: Maybe<Scalars['Timestamp']['output']>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** The notable entity for this note */
  notable?: Maybe<NotableEntity>;
  /** ID of the notable entity matching notable_type. */
  notable_id: Scalars['ULID']['output'];
  /** Polymorphic type discriminator for the notable entity (see NotableEntity). */
  notable_type: Scalars['String']['output'];
  /** When the note was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user who owns this note */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
};

/** A paginated list of Note items. */
export type NotePaginator = {
  __typename?: 'NotePaginator';
  /** A list of Note items. */
  data: Array<Note>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Allows ordering a list of records. */
export type OrderByClause = {
  /** The column that is used for ordering. */
  column: Scalars['String']['input'];
  /** The direction that is used for ordering. */
  order: SortOrder;
};

/** Aggregate functions when ordering by a relation without specifying a column. */
export enum OrderByRelationAggregateFunction {
  /** Amount of items. */
  Count = 'COUNT'
}

/** Aggregate functions when ordering by a relation that may specify a column. */
export enum OrderByRelationWithColumnAggregateFunction {
  /** Average. */
  Avg = 'AVG',
  /** Amount of items. */
  Count = 'COUNT',
  /** Maximum. */
  Max = 'MAX',
  /** Minimum. */
  Min = 'MIN',
  /** Sum. */
  Sum = 'SUM'
}

/** Information about pagination using a fully featured paginator. */
export type PaginatorInfo = {
  __typename?: 'PaginatorInfo';
  /** Number of items in the current page. */
  count: Scalars['Int']['output'];
  /** Index of the current page. */
  currentPage: Scalars['Int']['output'];
  /** Index of the first item in the current page. */
  firstItem?: Maybe<Scalars['Int']['output']>;
  /** Are there more pages after this one? */
  hasMorePages: Scalars['Boolean']['output'];
  /** Index of the last item in the current page. */
  lastItem?: Maybe<Scalars['Int']['output']>;
  /** Index of the last available page. */
  lastPage: Scalars['Int']['output'];
  /** Number of items per page. */
  perPage: Scalars['Int']['output'];
  /** Number of total available items. */
  total: Scalars['Int']['output'];
};

/** Pay-to-play platform */
export type Platform = {
  __typename?: 'Platform';
  /** Auditions sourced from this platform */
  auditions: Array<Audition>;
  /** When the platform was created */
  created_at: Scalars['Timestamp']['output'];
  /** External platform ID */
  external_id?: Maybe<Scalars['String']['output']>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Platform name */
  name: Scalars['String']['output'];
  /** Notes for this platform (paginated) */
  notes: NotePaginator;
  /** When the platform was last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** Platform URL */
  url?: Maybe<Scalars['String']['output']>;
  /** The user who owns this platform */
  user: User;
  /** User ID */
  user_id: Scalars['ULID']['output'];
  /** Username on platform */
  username?: Maybe<Scalars['String']['output']>;
};


/** Pay-to-play platform */
export type PlatformNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** A paginated list of Platform items. */
export type PlatformPaginator = {
  __typename?: 'PlatformPaginator';
  /** A list of Platform items. */
  data: Array<Platform>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Precision level of currency conversion */
export enum Precision {
  Estimated = 'ESTIMATED',
  Exact = 'EXACT'
}

/** Project category enum */
export enum ProjectCategory {
  Animation = 'ANIMATION',
  Announcement = 'ANNOUNCEMENT',
  Audiobook = 'AUDIOBOOK',
  Coaching = 'COACHING',
  Commercial = 'COMMERCIAL',
  Corporate = 'CORPORATE',
  Documentary = 'DOCUMENTARY',
  Dubbing = 'DUBBING',
  Elearning = 'ELEARNING',
  Explainer = 'EXPLAINER',
  Film = 'FILM',
  Ivr = 'IVR',
  Meditation = 'MEDITATION',
  Narration = 'NARRATION',
  Other = 'OTHER',
  Podcast = 'PODCAST',
  Promo = 'PROMO',
  RadioImaging = 'RADIO_IMAGING',
  Trailer = 'TRAILER',
  TvSeries = 'TV_SERIES',
  Unknown = 'UNKNOWN',
  VideoGame = 'VIDEO_GAME'
}

/** Indicates what fields are available at the top level of a query operation. */
export type Query = {
  __typename?: 'Query';
  /** List activities for authenticated user */
  activities: ActivityPaginator;
  /** Get a single agent by ID */
  agent?: Maybe<Agent>;
  /** List all agents for authenticated user */
  agents: AgentPaginator;
  /** Get a single attachment by ID */
  attachment?: Maybe<Attachment>;
  /** List all attachments for authenticated user */
  attachments: AttachmentPaginator;
  /** Get a single audition by ID */
  audition?: Maybe<Audition>;
  /**
   * Get audition chart data with intelligent time window expansion.
   * Backend determines optimal visualization window (e.g., MTD on day 3 = 30 days).
   * Returns only chart points showing auditions submitted over time.
   */
  auditionChart: AuditionChartResponse;
  /**
   * Get audition metrics with strict period boundaries.
   * MTD = exactly month start to today, no expansion.
   * Returns audition count with trends and booking rate.
   */
  auditionMetrics: AuditionMetricsResponse;
  /** List all auditions for authenticated user */
  auditions: AuditionPaginator;
  /** Get a single business profile by ID */
  businessProfile?: Maybe<BusinessProfile>;
  /** List all business profiles for authenticated user */
  businessProfiles: BusinessProfilePaginator;
  /** Get a single client by ID */
  client?: Maybe<Client>;
  /** List all clients for authenticated user */
  clients: ClientPaginator;
  /** Get a single contact by ID */
  contact?: Maybe<Contact>;
  /** List all contacts for authenticated user */
  contacts: ContactPaginator;
  /** Get a single expense by ID */
  expense?: Maybe<Expense>;
  /** Get a single expense definition by ID */
  expenseDefinition?: Maybe<ExpenseDefinition>;
  /** List all expense definitions for authenticated user */
  expenseDefinitions: ExpenseDefinitionPaginator;
  /** List all expenses for authenticated user */
  expenses: ExpensePaginator;
  /** Get a single invoice by ID */
  invoice?: Maybe<Invoice>;
  /** Get a single invoice item by ID */
  invoiceItem?: Maybe<InvoiceItem>;
  /** List all invoice items for authenticated user */
  invoiceItems: InvoiceItemPaginator;
  /** List all invoices for authenticated user */
  invoices: InvoicePaginator;
  /** Get a single job by ID */
  job?: Maybe<Job>;
  /** List all jobs for authenticated user */
  jobs: JobPaginator;
  /** Get the authenticated user */
  me: User;
  /** Get settings for the authenticated user */
  mySettings?: Maybe<Settings>;
  /** Get a single note by ID */
  note?: Maybe<Note>;
  /** List all notes for authenticated user */
  notes: NotePaginator;
  /** Get a single platform by ID */
  platform?: Maybe<Platform>;
  /** List all platforms for authenticated user */
  platforms: PlatformPaginator;
  /**
   * Get revenue grouped by project category.
   * Paid revenue is period-bound; in-flight revenue is a snapshot.
   * Returns the top `take` categories by total (paid + in-flight).
   */
  revenueByCategory: RevenueByCategoryResponse;
  /**
   * Get revenue grouped by source (platforms, agents, direct clients).
   * Paid revenue is period-bound; in-flight revenue is a snapshot.
   * Returns the top `take` sources by total (paid + in-flight).
   */
  revenueBySource: RevenueBySourceResponse;
  /**
   * Get revenue chart data with intelligent time window expansion.
   * Backend determines optimal visualization window (e.g., MTD on day 3 = 30 days).
   * Returns only chart points, no metrics.
   */
  revenueChart: RevenueChartResponse;
  /**
   * Get revenue metrics with strict period boundaries.
   * MTD = exactly month start to today, no expansion.
   * Returns current/pipeline/in-flight revenue with trends and comparisons.
   */
  revenueMetrics: RevenueMetricsResponse;
  /** Run a user-scoped full-text search across one or many entity types. */
  search: SearchResultPaginator;
  /** Get a single usage right by ID */
  usageRight?: Maybe<UsageRight>;
  /** List all usage rights for authenticated user */
  usageRights: UsageRightPaginator;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryActivitiesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
  where?: InputMaybe<QueryActivitiesWhereWhereConditions>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAgentArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAgentsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAttachmentArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAttachmentsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAuditionArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAuditionChartArgs = {
  period?: CompactRange;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAuditionMetricsArgs = {
  period?: CompactRange;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryAuditionsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryBusinessProfileArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryBusinessProfilesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryClientArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryClientsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryContactArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryContactsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryExpenseArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryExpenseDefinitionArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryExpenseDefinitionsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryExpensesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryInvoiceArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryInvoiceItemArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryInvoiceItemsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryInvoicesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryJobArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryJobsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryNoteArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryPlatformArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryPlatformsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryRevenueByCategoryArgs = {
  baseCurrency?: InputMaybe<Scalars['String']['input']>;
  period?: CompactRange;
  take?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryRevenueBySourceArgs = {
  baseCurrency?: InputMaybe<Scalars['String']['input']>;
  period?: CompactRange;
  take?: InputMaybe<Scalars['Int']['input']>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryRevenueChartArgs = {
  baseCurrency?: InputMaybe<Scalars['String']['input']>;
  period?: CompactRange;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryRevenueMetricsArgs = {
  baseCurrency?: InputMaybe<Scalars['String']['input']>;
  period?: CompactRange;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QuerySearchArgs = {
  first?: InputMaybe<Scalars['Int']['input']>;
  page?: InputMaybe<Scalars['Int']['input']>;
  query?: InputMaybe<Scalars['String']['input']>;
  types?: InputMaybe<Array<SearchEntityType>>;
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryUsageRightArgs = {
  id: Scalars['ULID']['input'];
};


/** Indicates what fields are available at the top level of a query operation. */
export type QueryUsageRightsArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** Allowed column names for Query.activities.where. */
export enum QueryActivitiesWhereColumn {
  Action = 'ACTION',
  SnoozedUntil = 'SNOOZED_UNTIL'
}

/** Dynamic WHERE conditions for the `where` argument of the query `activities`. */
export type QueryActivitiesWhereWhereConditions = {
  /** A set of conditions that requires all conditions to match. */
  AND?: InputMaybe<Array<QueryActivitiesWhereWhereConditions>>;
  /** Check whether a relation exists. Extra conditions or a minimum amount can be applied. */
  HAS?: InputMaybe<QueryActivitiesWhereWhereConditionsRelation>;
  /** A set of conditions that requires at least one condition to match. */
  OR?: InputMaybe<Array<QueryActivitiesWhereWhereConditions>>;
  /** The column that is used for the condition. */
  column?: InputMaybe<QueryActivitiesWhereColumn>;
  /** The operator that is used for the condition. */
  operator?: InputMaybe<SqlOperator>;
  /** The value that is used for the condition. */
  value?: InputMaybe<Scalars['Mixed']['input']>;
};

/** Dynamic WHERE HAS conditions for the `where` argument of the query `activities`. */
export type QueryActivitiesWhereWhereConditionsHasCondition = {
  /** A set of conditions that requires all conditions to match. */
  AND?: InputMaybe<Array<QueryActivitiesWhereWhereConditionsHasCondition>>;
  /** Check whether a relation exists. Extra conditions or a minimum amount can be applied. */
  HAS?: InputMaybe<QueryActivitiesWhereWhereConditionsRelation>;
  /** A set of conditions that requires at least one condition to match. */
  OR?: InputMaybe<Array<QueryActivitiesWhereWhereConditionsHasCondition>>;
  /** The column that is used for the condition. */
  column?: InputMaybe<Scalars['String']['input']>;
  /** The operator that is used for the condition. */
  operator?: InputMaybe<SqlOperator>;
  /** The value that is used for the condition. */
  value?: InputMaybe<Scalars['Mixed']['input']>;
};

/** Dynamic HAS conditions for WHERE conditions for the `where` argument of the query `activities`. */
export type QueryActivitiesWhereWhereConditionsRelation = {
  /** The amount to test. */
  amount?: InputMaybe<Scalars['Int']['input']>;
  /** Additional condition logic. */
  condition?: InputMaybe<QueryActivitiesWhereWhereConditionsHasCondition>;
  /** The comparison operator to test against the amount. */
  operator?: InputMaybe<SqlOperator>;
  /** The relation that is checked. */
  relation: Scalars['String']['input'];
};

/** Rate type enum */
export enum RateType {
  Buyout = 'BUYOUT',
  Flat = 'FLAT',
  Hourly = 'HOURLY',
  PerFinishedHour = 'PER_FINISHED_HOUR',
  PerLine = 'PER_LINE',
  PerWord = 'PER_WORD'
}

/** Error response for code request. */
export type RequestAuthenticationCodeErrorResponse = {
  __typename?: 'RequestAuthenticationCodeErrorResponse';
  /** Human-readable error message. */
  message: Scalars['String']['output'];
};

/** Input for requesting an authentication code for an existing user. */
export type RequestAuthenticationCodeInput = {
  /** Email address to receive the authentication code. */
  email: Scalars['String']['input'];
};

/** Success response for code request. */
export type RequestAuthenticationCodeOkResponse = {
  __typename?: 'RequestAuthenticationCodeOkResponse';
  /** True when the request succeeded. */
  ok: Scalars['Boolean']['output'];
};

/** Union response for code request. */
export type RequestAuthenticationCodeResponse = RequestAuthenticationCodeErrorResponse | RequestAuthenticationCodeOkResponse;

export type RevenueByCategoryEntry = {
  __typename?: 'RevenueByCategoryEntry';
  /** Project category for the revenue bucket. */
  category: ProjectCategory;
  /** In-flight revenue snapshot (unpaid invoices + unbilled active jobs). */
  in_flight: Money;
  /** Paid revenue within the requested period. */
  paid: Money;
  /** Percentage of total (paid + in-flight) for this category. */
  percentage_of_total: Scalars['Float']['output'];
};

export type RevenueByCategoryResponse = {
  __typename?: 'RevenueByCategoryResponse';
  /** Base currency used for all metrics totals. */
  baseCurrency: Scalars['String']['output'];
  /** Revenue grouped by project category. */
  categories: Array<RevenueByCategoryEntry>;
  /** The exact period boundaries used for paid revenue (no expansion). */
  period: DateRange;
};

export type RevenueBySourceEntry = {
  __typename?: 'RevenueBySourceEntry';
  /** In-flight revenue snapshot (unpaid invoices + unbilled active jobs). */
  in_flight: Money;
  /** Paid revenue within the requested period. */
  paid: Money;
  /** Percentage of total (paid + in-flight) for this source. */
  percentage_of_total: Scalars['Float']['output'];
  /** Human-readable label for the source (platform, agent, direct client, or unknown). */
  source_name: Scalars['String']['output'];
  /** The type of source (platform, agent, direct client, or unknown). */
  source_type: RevenueSourceType;
};

export type RevenueBySourceResponse = {
  __typename?: 'RevenueBySourceResponse';
  /** Base currency used for all metrics totals. */
  baseCurrency: Scalars['String']['output'];
  /** The exact period boundaries used for paid revenue (no expansion). */
  period: DateRange;
  /** Revenue grouped by source. */
  sources: Array<RevenueBySourceEntry>;
};

export type RevenueChartResponse = ChartResponse & {
  __typename?: 'RevenueChartResponse';
  /** Currency used for revenue calculations */
  baseCurrency: Scalars['String']['output'];
  /** Chart data points with timestamp and value */
  chart: Array<ChartPoint>;
  /**
   * The actual time window used for the chart.
   * Early MTD/QTD/YTD requests are automatically expanded for better visualization.
   */
  effectiveWindow: DateRangeWindow;
  /** The range of data requested - either a compact range (1W, MTD, etc.) or explicit dates */
  range: ChartRange;
};

export type RevenueMetrics = {
  __typename?: 'RevenueMetrics';
  /** Paid revenue within the requested period. */
  current: RevenueMetricsSnapshot;
  /** Money still expected to be collected (unpaid invoices + unbilled active jobs). */
  in_flight: RevenuePipelineMetrics;
  /** Value of active jobs (pipeline snapshot, not period-bound). */
  pipeline: RevenuePipelineMetrics;
};

export type RevenueMetricsResponse = {
  __typename?: 'RevenueMetricsResponse';
  /** Base currency used for all metrics totals. */
  baseCurrency: Scalars['String']['output'];
  /** Current, pipeline, and in-flight revenue metrics. */
  metrics: RevenueMetrics;
  /** The exact period boundaries used for metrics calculation (no expansion) */
  period: DateRange;
};

export type RevenueMetricsSnapshot = {
  __typename?: 'RevenueMetricsSnapshot';
  /** Total revenue for the comparison period immediately preceding the current range. */
  comparison_total: Money;
  /** Precision of the aggregated total (EXACT when all conversions are exact). */
  precision: Precision;
  /** Total revenue for the period, in the requested base currency. */
  total: Money;
  /** Percentage change vs the comparison period's total. */
  trend_percentage: Scalars['Float']['output'];
};

/** Lightweight metrics for pipeline-style totals (no trend or comparisons). */
export type RevenuePipelineMetrics = {
  __typename?: 'RevenuePipelineMetrics';
  /** Precision of the aggregated total (EXACT when all conversions are exact). */
  precision: Precision;
  /** Total value in the requested base currency. */
  total: Money;
};

export enum RevenueSourceType {
  Agent = 'AGENT',
  Direct = 'DIRECT',
  Platform = 'PLATFORM',
  Unknown = 'UNKNOWN'
}

/** Error response for token/session revocation. */
export type RevokeTokenErrorResponse = {
  __typename?: 'RevokeTokenErrorResponse';
  /** Human-readable error message. */
  message: Scalars['String']['output'];
};

/** Success response for token/session revocation. */
export type RevokeTokenOkResponse = {
  __typename?: 'RevokeTokenOkResponse';
  /** True when the request succeeded. */
  ok: Scalars['Boolean']['output'];
};

/** Union response for token/session revocation. */
export type RevokeTokenResponse = RevokeTokenErrorResponse | RevokeTokenOkResponse;

/** The available SQL operators that are used to filter query results. */
export enum SqlOperator {
  /** Whether a value is within a range of values (`BETWEEN`) */
  Between = 'BETWEEN',
  /** Equal operator (`=`) */
  Eq = 'EQ',
  /** Greater than operator (`>`) */
  Gt = 'GT',
  /** Greater than or equal operator (`>=`) */
  Gte = 'GTE',
  /** Whether a value is within a set of values (`IN`) */
  In = 'IN',
  /** Whether a value is not null (`IS NOT NULL`) */
  IsNotNull = 'IS_NOT_NULL',
  /** Whether a value is null (`IS NULL`) */
  IsNull = 'IS_NULL',
  /** Simple pattern matching (`LIKE`) */
  Like = 'LIKE',
  /** Less than operator (`<`) */
  Lt = 'LT',
  /** Less than or equal operator (`<=`) */
  Lte = 'LTE',
  /** Not equal operator (`!=`) */
  Neq = 'NEQ',
  /** Whether a value is not within a range of values (`NOT BETWEEN`) */
  NotBetween = 'NOT_BETWEEN',
  /** Whether a value is not within a set of values (`NOT IN`) */
  NotIn = 'NOT_IN',
  /** Negation of simple pattern matching (`NOT LIKE`) */
  NotLike = 'NOT_LIKE'
}

/** Union of searchable entities. */
export type SearchEntityResult = Agent | Audition | Client | Contact | Expense | Invoice | Job | Note | Platform;

/** Entity type filter for full-text search. */
export enum SearchEntityType {
  Agent = 'AGENT',
  Audition = 'AUDITION',
  Client = 'CLIENT',
  Contact = 'CONTACT',
  Expense = 'EXPENSE',
  Invoice = 'INVOICE',
  Job = 'JOB',
  Note = 'NOTE',
  Platform = 'PLATFORM'
}

/** Single field match explanation for a search hit. */
export type SearchMatch = {
  __typename?: 'SearchMatch';
  end: Scalars['Int']['output'];
  field: Scalars['String']['output'];
  matchedText: Scalars['String']['output'];
  snippet: Scalars['String']['output'];
  start: Scalars['Int']['output'];
  text: Scalars['String']['output'];
};

/** Pagination metadata for search. */
export type SearchPaginatorInfo = {
  __typename?: 'SearchPaginatorInfo';
  currentPage: Scalars['Int']['output'];
  hasMorePages: Scalars['Boolean']['output'];
  lastPage: Scalars['Int']['output'];
  perPage: Scalars['Int']['output'];
  total: Scalars['Int']['output'];
};

/** Search hit wrapper with the resolved entity and match metadata. */
export type SearchResultItem = {
  __typename?: 'SearchResultItem';
  entity: SearchEntityResult;
  matches: Array<SearchMatch>;
};

/** Paginated search payload. */
export type SearchResultPaginator = {
  __typename?: 'SearchResultPaginator';
  data: Array<SearchResultItem>;
  paginatorInfo: SearchPaginatorInfo;
};

/** User settings and preferences */
export type Settings = {
  __typename?: 'Settings';
  /** When the settings were created */
  created_at: Scalars['Timestamp']['output'];
  /** Currency code */
  currency: Scalars['String']['output'];
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Language code */
  language: Scalars['String']['output'];
  /** Timezone */
  timezone: Scalars['String']['output'];
  /** When the settings were last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The user these settings belong to */
  user: User;
  /** User ID this settings belongs to */
  user_id: Scalars['ULID']['output'];
};

/** Error response for sign-up and code request. */
export type SignUpAndRequestAuthenticationTokenErrorResponse = {
  __typename?: 'SignUpAndRequestAuthenticationTokenErrorResponse';
  /** Human-readable error message. */
  message: Scalars['String']['output'];
};

/** Input for registering (if needed) and requesting an authentication code. */
export type SignUpAndRequestAuthenticationTokenInput = {
  /** Email address to receive the authentication code. */
  email: Scalars['String']['input'];
  /** Display name for new users. */
  name: Scalars['String']['input'];
};

/** Success response for sign-up and code request. */
export type SignUpAndRequestAuthenticationTokenOkResponse = {
  __typename?: 'SignUpAndRequestAuthenticationTokenOkResponse';
  /** True when the request succeeded. */
  ok: Scalars['Boolean']['output'];
};

/** Union response for sign-up and code request. */
export type SignUpAndRequestAuthenticationTokenResponse = SignUpAndRequestAuthenticationTokenErrorResponse | SignUpAndRequestAuthenticationTokenOkResponse;

/** Input for snoozing an activity */
export type SnoozeActivityInput = {
  id: Scalars['ULID']['input'];
  snoozed_until: Scalars['Timestamp']['input'];
};

/** Directions for ordering a list of records. */
export enum SortOrder {
  /** Sort records in ascending order. */
  Asc = 'ASC',
  /** Sort records in descending order. */
  Desc = 'DESC'
}

/** Entities that can be audition sources */
export type SourceableEntity = Contact | Platform;

/** Generic time series data point for any time-based chart */
export type TimeSeriesDataPoint = {
  __typename?: 'TimeSeriesDataPoint';
  /** UTC timestamp in milliseconds representing the date/time for this data point */
  timestamp: Scalars['Timestamp']['output'];
  /** Value at this point in time (typically in cents for monetary values) */
  value: Scalars['Int']['output'];
};

/** Specify if you want to include or exclude trashed results from a query. */
export enum Trashed {
  /** Only return trashed results. */
  Only = 'ONLY',
  /** Return both trashed and non-trashed results. */
  With = 'WITH',
  /** Only return non-trashed results. */
  Without = 'WITHOUT'
}

/** Input for updating an agent contact */
export type UpdateAgentContactInput = {
  /** City */
  address_city?: InputMaybe<Scalars['String']['input']>;
  /** Country */
  address_country?: InputMaybe<Scalars['String']['input']>;
  /** Postal code */
  address_postal?: InputMaybe<Scalars['String']['input']>;
  /** State/Region */
  address_state?: InputMaybe<Scalars['String']['input']>;
  /** Street address */
  address_street?: InputMaybe<Scalars['String']['input']>;
  /** Email */
  email?: InputMaybe<Scalars['String']['input']>;
  /** Last contacted timestamp */
  last_contacted_at?: InputMaybe<Scalars['Timestamp']['input']>;
  /** Contact name */
  name?: InputMaybe<Scalars['String']['input']>;
  /** Phone */
  phone?: InputMaybe<Scalars['String']['input']>;
  /** Phone extension */
  phone_ext?: InputMaybe<Scalars['String']['input']>;
};

/** Input for updating an agent and/or primary contact */
export type UpdateAgentWithContactInput = {
  /** Agency name */
  agency_name?: InputMaybe<Scalars['String']['input']>;
  /** Commission rate in basis points */
  commission_rate?: InputMaybe<Scalars['Int']['input']>;
  /** Contact data */
  contact?: InputMaybe<UpdateAgentContactInput>;
  /** Contract end date */
  contract_end?: InputMaybe<Scalars['Timestamp']['input']>;
  /** Contract start date */
  contract_start?: InputMaybe<Scalars['Timestamp']['input']>;
  /** Whether this agent is exclusive */
  is_exclusive?: InputMaybe<Scalars['Boolean']['input']>;
  /** Territories covered by the agent */
  territories?: InputMaybe<Array<Scalars['String']['input']>>;
};

/** Input for updating an attachment */
export type UpdateAttachmentInput = {
  category?: InputMaybe<AttachmentCategory>;
  disk?: InputMaybe<Scalars['String']['input']>;
  filename?: InputMaybe<Scalars['String']['input']>;
  metadata?: InputMaybe<Scalars['JSON']['input']>;
  mime_type?: InputMaybe<Scalars['String']['input']>;
  original_filename?: InputMaybe<Scalars['String']['input']>;
  path?: InputMaybe<Scalars['String']['input']>;
  size?: InputMaybe<Scalars['Int']['input']>;
};

/** Input for updating an audition */
export type UpdateAuditionInput = {
  brand_name?: InputMaybe<Scalars['String']['input']>;
  budget_max?: InputMaybe<Scalars['Int']['input']>;
  budget_min?: InputMaybe<Scalars['Int']['input']>;
  category?: InputMaybe<ProjectCategory>;
  character_name?: InputMaybe<Scalars['String']['input']>;
  project_deadline?: InputMaybe<Scalars['Timestamp']['input']>;
  project_title?: InputMaybe<Scalars['String']['input']>;
  quoted_rate?: InputMaybe<Scalars['Int']['input']>;
  rate_type?: InputMaybe<RateType>;
  response_deadline?: InputMaybe<Scalars['Timestamp']['input']>;
  source_reference?: InputMaybe<Scalars['String']['input']>;
  /** ID of the source entity matching sourceable_type. */
  sourceable_id?: InputMaybe<Scalars['ULID']['input']>;
  /** Polymorphic type discriminator for the source entity (see SourceableEntity). */
  sourceable_type?: InputMaybe<Scalars['String']['input']>;
  status?: InputMaybe<AuditionStatus>;
  submitted_at?: InputMaybe<Scalars['Timestamp']['input']>;
  word_count?: InputMaybe<Scalars['Int']['input']>;
};

/** Input for updating a business profile */
export type UpdateBusinessProfileInput = {
  address_city?: InputMaybe<Scalars['String']['input']>;
  address_country?: InputMaybe<Scalars['String']['input']>;
  address_postal?: InputMaybe<Scalars['String']['input']>;
  address_state?: InputMaybe<Scalars['String']['input']>;
  address_street?: InputMaybe<Scalars['String']['input']>;
  business_name?: InputMaybe<Scalars['String']['input']>;
  email?: InputMaybe<Scalars['String']['input']>;
  logo_path?: InputMaybe<Scalars['String']['input']>;
  payment_instructions?: InputMaybe<Scalars['String']['input']>;
  phone?: InputMaybe<Scalars['String']['input']>;
};

/** Input for updating a client */
export type UpdateClientInput = {
  industry?: InputMaybe<Scalars['String']['input']>;
  payment_terms?: InputMaybe<Scalars['String']['input']>;
  type?: InputMaybe<ClientType>;
};

/** Input for updating a contact */
export type UpdateContactInput = {
  address_city?: InputMaybe<Scalars['String']['input']>;
  address_country?: InputMaybe<Scalars['String']['input']>;
  address_postal?: InputMaybe<Scalars['String']['input']>;
  address_state?: InputMaybe<Scalars['String']['input']>;
  address_street?: InputMaybe<Scalars['String']['input']>;
  /** ID of the related entity matching contactable_type. */
  contactable_id?: InputMaybe<Scalars['ULID']['input']>;
  /** Polymorphic type discriminator for the related entity (see ContactableEntity). */
  contactable_type?: InputMaybe<Scalars['String']['input']>;
  email?: InputMaybe<Scalars['String']['input']>;
  last_contacted_at?: InputMaybe<Scalars['Timestamp']['input']>;
  name?: InputMaybe<Scalars['String']['input']>;
  phone?: InputMaybe<Scalars['String']['input']>;
  phone_ext?: InputMaybe<Scalars['String']['input']>;
};

/** Input for updating an expense definition */
export type UpdateExpenseDefinitionInput = {
  amount?: InputMaybe<MoneyInput>;
  category?: InputMaybe<ExpenseCategory>;
  ends_at?: InputMaybe<Scalars['Timestamp']['input']>;
  is_active?: InputMaybe<Scalars['Boolean']['input']>;
  name?: InputMaybe<Scalars['String']['input']>;
  recurrence?: InputMaybe<ExpenseRecurrence>;
  recurrence_day?: InputMaybe<Scalars['Int']['input']>;
  starts_at?: InputMaybe<Scalars['Timestamp']['input']>;
};

/** Input for updating an expense */
export type UpdateExpenseInput = {
  amount?: InputMaybe<MoneyInput>;
  category?: InputMaybe<ExpenseCategory>;
  date?: InputMaybe<Scalars['Timestamp']['input']>;
  description?: InputMaybe<Scalars['String']['input']>;
  expense_definition_id?: InputMaybe<Scalars['ULID']['input']>;
};

/** Input for updating an invoice */
export type UpdateInvoiceInput = {
  client_id?: InputMaybe<Scalars['ULID']['input']>;
  due_at?: InputMaybe<Scalars['Timestamp']['input']>;
  invoice_number?: InputMaybe<Scalars['String']['input']>;
  issued_at?: InputMaybe<Scalars['Timestamp']['input']>;
  job_id?: InputMaybe<Scalars['ULID']['input']>;
  paid_at?: InputMaybe<Scalars['Timestamp']['input']>;
  status?: InputMaybe<InvoiceStatus>;
  subtotal?: InputMaybe<MoneyInput>;
  tax_amount?: InputMaybe<MoneyInput>;
  tax_rate?: InputMaybe<Scalars['Float']['input']>;
  total?: InputMaybe<MoneyInput>;
};

/** Input for updating an invoice item */
export type UpdateInvoiceItemInput = {
  amount?: InputMaybe<Scalars['Int']['input']>;
  description?: InputMaybe<Scalars['String']['input']>;
  quantity?: InputMaybe<Scalars['Float']['input']>;
  unit_price?: InputMaybe<Scalars['Int']['input']>;
};

/** Input for updating a job */
export type UpdateJobInput = {
  actual_hours?: InputMaybe<Scalars['Float']['input']>;
  agent?: InputMaybe<CreateJobAgentRelationInput>;
  audition?: InputMaybe<CreateJobAuditionRelationInput>;
  brand_name?: InputMaybe<Scalars['String']['input']>;
  category?: InputMaybe<ProjectCategory>;
  character_name?: InputMaybe<Scalars['String']['input']>;
  client?: InputMaybe<CreateJobClientRelationInput>;
  contracted_rate?: InputMaybe<MoneyInput>;
  delivered_at?: InputMaybe<Scalars['Timestamp']['input']>;
  delivery_deadline?: InputMaybe<Scalars['Timestamp']['input']>;
  estimated_hours?: InputMaybe<Scalars['Float']['input']>;
  project_title?: InputMaybe<Scalars['String']['input']>;
  rate_type?: InputMaybe<RateType>;
  session_date?: InputMaybe<Scalars['Timestamp']['input']>;
  status?: InputMaybe<JobStatus>;
  word_count?: InputMaybe<Scalars['Int']['input']>;
};

/** Input for updating a note */
export type UpdateNoteInput = {
  content: Scalars['String']['input'];
};

/** Input for updating a platform */
export type UpdatePlatformInput = {
  external_id?: InputMaybe<Scalars['String']['input']>;
  name?: InputMaybe<Scalars['String']['input']>;
  url?: InputMaybe<Scalars['String']['input']>;
  username?: InputMaybe<Scalars['String']['input']>;
};

/** Input for updating settings */
export type UpdateSettingsInput = {
  currency?: InputMaybe<Scalars['String']['input']>;
  language?: InputMaybe<Scalars['String']['input']>;
  timezone?: InputMaybe<Scalars['String']['input']>;
};

/** Input for updating usage rights */
export type UpdateUsageRightInput = {
  ai_rights_granted?: InputMaybe<Scalars['Boolean']['input']>;
  duration_months?: InputMaybe<Scalars['Int']['input']>;
  duration_type?: InputMaybe<DurationType>;
  exclusivity?: InputMaybe<Scalars['Boolean']['input']>;
  exclusivity_category?: InputMaybe<Scalars['String']['input']>;
  expiration_date?: InputMaybe<Scalars['Timestamp']['input']>;
  geographic_scope?: InputMaybe<GeographicScope>;
  media_types?: InputMaybe<Array<UsageMediaType>>;
  renewal_reminder_sent?: InputMaybe<Scalars['Boolean']['input']>;
  start_date?: InputMaybe<Scalars['Timestamp']['input']>;
  type?: InputMaybe<UsageType>;
};

/** Input for updating a user */
export type UpdateUserInput = {
  /** User's display name */
  name?: InputMaybe<Scalars['String']['input']>;
};

/** Entities that can be assigned usage rights */
export type UsableEntity = Audition | Job;

/** Media type enum */
export enum UsageMediaType {
  AllMedia = 'ALL_MEDIA',
  Cinema = 'CINEMA',
  Digital = 'DIGITAL',
  Internal = 'INTERNAL',
  Outdoor = 'OUTDOOR',
  Podcast = 'PODCAST',
  Print = 'PRINT',
  Radio = 'RADIO',
  SocialMedia = 'SOCIAL_MEDIA',
  Streaming = 'STREAMING',
  Tv = 'TV',
  VideoGame = 'VIDEO_GAME'
}

/** Usage rights details */
export type UsageRight = {
  __typename?: 'UsageRight';
  /** AI rights granted */
  ai_rights_granted: Scalars['Boolean']['output'];
  /** When the usage rights were created */
  created_at: Scalars['Timestamp']['output'];
  /** Duration in months */
  duration_months?: Maybe<Scalars['Int']['output']>;
  /** Duration type */
  duration_type: DurationType;
  /** Exclusivity flag */
  exclusivity: Scalars['Boolean']['output'];
  /** Exclusivity category */
  exclusivity_category?: Maybe<Scalars['String']['output']>;
  /** Expiration date */
  expiration_date?: Maybe<Scalars['Timestamp']['output']>;
  /** Geographic scope */
  geographic_scope: GeographicScope;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** Media types */
  media_types: Array<UsageMediaType>;
  /** Notes for this usage right (paginated) */
  notes: NotePaginator;
  /** Renewal reminder sent */
  renewal_reminder_sent: Scalars['Boolean']['output'];
  /** Start date */
  start_date?: Maybe<Scalars['Timestamp']['output']>;
  /** Usage type */
  type: UsageType;
  /** When the usage rights were last updated */
  updated_at: Scalars['Timestamp']['output'];
  /** The usable entity (audition or job) */
  usable?: Maybe<UsableEntity>;
  /** ID of the usable entity matching usable_type. */
  usable_id: Scalars['ULID']['output'];
  /** Polymorphic type discriminator for the usable entity (see UsableEntity). */
  usable_type: Scalars['String']['output'];
};


/** Usage rights details */
export type UsageRightNotesArgs = {
  first?: Scalars['Int']['input'];
  page?: InputMaybe<Scalars['Int']['input']>;
};

/** A paginated list of UsageRight items. */
export type UsageRightPaginator = {
  __typename?: 'UsageRightPaginator';
  /** A list of UsageRight items. */
  data: Array<UsageRight>;
  /** Pagination information about the list of items. */
  paginatorInfo: PaginatorInfo;
};

/** Usage rights type */
export enum UsageType {
  Broadcast = 'BROADCAST',
  NonBroadcast = 'NON_BROADCAST'
}

/** Account of a person who uses this application. */
export type User = {
  __typename?: 'User';
  /** Business profile */
  businessProfile?: Maybe<BusinessProfile>;
  /** When the account was created */
  created_at: Scalars['Timestamp']['output'];
  /** Unique email address */
  email: Scalars['String']['output'];
  /** When the email was verified */
  email_verified_at?: Maybe<Scalars['Timestamp']['output']>;
  /** Unique primary key (ULID) */
  id: Scalars['ULID']['output'];
  /** User's display name */
  name: Scalars['String']['output'];
  /** User settings */
  settings?: Maybe<Settings>;
  /** When the account was last updated */
  updated_at: Scalars['Timestamp']['output'];
};

/** Dynamic WHERE conditions for queries. */
export type WhereConditions = {
  /** A set of conditions that requires all conditions to match. */
  AND?: InputMaybe<Array<WhereConditions>>;
  /** Check whether a relation exists. Extra conditions or a minimum amount can be applied. */
  HAS?: InputMaybe<WhereConditionsRelation>;
  /** A set of conditions that requires at least one condition to match. */
  OR?: InputMaybe<Array<WhereConditions>>;
  /** The column that is used for the condition. */
  column?: InputMaybe<Scalars['String']['input']>;
  /** The operator that is used for the condition. */
  operator?: InputMaybe<SqlOperator>;
  /** The value that is used for the condition. */
  value?: InputMaybe<Scalars['Mixed']['input']>;
};

/** Dynamic WHERE conditions for HAS conditions. */
export type WhereConditionsHasCondition = {
  /** A set of conditions that requires all conditions to match. */
  AND?: InputMaybe<Array<WhereConditionsHasCondition>>;
  /** Check whether a relation exists. Extra conditions or a minimum amount can be applied. */
  HAS?: InputMaybe<WhereConditionsRelation>;
  /** A set of conditions that requires at least one condition to match. */
  OR?: InputMaybe<Array<WhereConditionsHasCondition>>;
  /** The column that is used for the condition. */
  column?: InputMaybe<Scalars['String']['input']>;
  /** The operator that is used for the condition. */
  operator?: InputMaybe<SqlOperator>;
  /** The value that is used for the condition. */
  value?: InputMaybe<Scalars['Mixed']['input']>;
};

/** Dynamic HAS conditions for WHERE condition queries. */
export type WhereConditionsRelation = {
  /** The amount to test. */
  amount?: InputMaybe<Scalars['Int']['input']>;
  /** Additional condition logic. */
  condition?: InputMaybe<WhereConditionsHasCondition>;
  /** The comparison operator to test against the amount. */
  operator?: InputMaybe<SqlOperator>;
  /** The relation that is checked. */
  relation: Scalars['String']['input'];
};
