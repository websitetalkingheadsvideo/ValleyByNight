flowchart TD
  %% VbN Summary-Driven Conversion Pipeline

  A[PDF Book Library<br/>(/Books)] --> B{Has a good summary?}
  B -- No --> C[Generate Summary<br/>(/Books_summaries)]
  C --> D[QA Summary (spot-check)]
  D --> E{Pass?}
  E -- No --> C
  E -- Yes --> F[Summary Approved]

  B -- Yes --> F[Summary Approved]

  F --> G{Choose Conversion Mode}
  G --> H1[Mode A: Evidence-Backed Expansion<br/>Summary controls structure<br/>PDF controls facts]
  G --> H2[Mode B: Converted Edition<br/>Standardized reference format]
  G --> H3[Mode C: Expand then Audit<br/>(limited use / riskier)]

  H1 --> I1[Section-by-Section Expansion]
  I1 --> J1[Source Grounding Check<br/>(flag unsupported)]
  J1 --> K1[Normalize + Format<br/>(headings, terms, refs)]
  K1 --> L[Converted Output<br/>(/Books_md_ready or /Converted)]

  H2 --> I2[Map Summary to Schema<br/>(standard sections)]
  I2 --> J2[Extract Key Rules/Defs<br/>from PDF as needed]
  J2 --> K2[Normalize + Format]
  K2 --> L

  H3 --> I3[Creative Expansion from Summary]
  I3 --> J3[Strict PDF Audit + Corrections]
  J3 --> K3[Normalize + Format]
  K3 --> L

  %% Post-processing for VbN systems
  L --> M{Route Output}
  M --> N[Agent Knowledge Base<br/>(Rules/Lore/Locations)]
  M --> O[Database Ingestion Prep<br/>(entities, tables, tags)]
  M --> P[Human Review Queue<br/>(edge cases / high-risk)]

  %% Coverage tracking
  Q[Coverage Tracker<br/>(books vs summaries)] --- B
  Q --- C
  Q --- F

  %% Exceptions
  R[Exceptions Bucket<br/>(Clan Books, Council of Primogen)] --> S[Manual/Assisted Summary Plan]
  S --> C
