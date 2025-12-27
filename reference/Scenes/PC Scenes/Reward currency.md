# Reward Currency

**Pick 1–2, keep it simple**

Use rewards that feel good immediately and also matter long-term:

- **Willpower refresh** (or "Resolve"/"Edge" if you're abstracting)
- **XP / Bonus XP** (small, session-level)
- **Momentum tokens** (spend to re-roll / add a clue / smooth a social stumble)

## My Suggestion for VbN

- **Micro:** Willpower (frequent, small hits)
- **Macro:** XP (end-of-night summary)

## What Gets Rewarded

### 1) Nature Hits (rare, big)

Nature is "who you are when it matters."

Reward when the PC:

- pursues their Nature goal
- takes a meaningful risk/cost
- does it in a way consistent with established play

**Example:** A Director Nature takes control of a chaotic scene to impose order (even if it draws attention).

### 2) Demeanor Hits (common, small)

Demeanor is "how you present yourself."

Reward when the player consistently expresses it in dialogue choices:

- tone
- posture
- willingness to defer/needle/charm/etc.

**Example:** Bon Vivant demeanor keeps things light and social even under pressure.

### 3) Trait/Ability Expression (situational, small)

Reward when a player uses:

- an Ability-relevant approach in a scene where it matters
- a Merit/Flaw in a way that complicates or enriches play

**Example:** "Common Sense" prompts a cautious option; taking it avoids a social blunder → small reward.

## The Anti-Exploit Rule (keeps it honest)

Only reward if at least one is true:

- **Cost:** risk, lost advantage, social danger, time pressure, etc.
- **Constraint:** the choice closes off an easier option
- **Commitment:** repeated consistency over multiple scenes
- **Consequences:** NPCs react meaningfully (even mildly)

No reward for "free" in-character flavor that doesn't matter.

## How It Plugs Into Your Dialogue System (cleanly)

Each dialogue option already has metadata (intent/tone). Add alignment tags and a reward hint.

### Add These Fields to Each Option

```json
roleplay_alignment

nature: { aligns: true/false, strength: 0–2 }

demeanor: { aligns: true/false, strength: 0–2 }

traits: [ { type:"merit|flaw|background|ability", name:"", aligns:true, strength:0–2 } ]

cost_flags: ["social_risk", "masquerade_risk", "lost_time", "submission", "attention_drawn"]

reward_suggestion: { willpower: 0–1, xp: 0–1, token: 0–1 }
```

### Then the Reward Evaluator Runs After Selection:

- checks alignment strength
- checks cost flags / consequences
- applies reward within caps

## Hard Caps (prevents farming)

Per night (or per "chapter"):

- **Willpower rewards:** max 2–3
- **XP rewards:** max 1–2 "bonus ticks"
- **Nature big hit:** max 1

Also: cooldown on the same tag (e.g., can't earn "Demeanor: Cheerful deflection" twice in the same conversation).

## How Roland Scene Would Use It (quick example)

If PC's Demeanor is "Polite Courtier" (whatever you name it):

- **"Of course."** → Demeanor aligns (1), low cost → maybe no reward
- **"Immediately."** → aligns (1), plus urgency/time pressure → +1 small reward
- **Over-eager initiative option** → misaligns with "cautious" nature → no reward (or even sets a "reckless" drift flag)
