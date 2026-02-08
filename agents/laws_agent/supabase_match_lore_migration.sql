-- Create match_lore_embeddings RPC for vector similarity search.
-- Run in Supabase SQL Editor if the RPC doesn't exist.
-- Assumes lore_embeddings has: id, content_text, title, source, metadata, embedding (vector).
-- Embedding dimension must match your model (1536 for text-embedding-3-small / ada-002).

CREATE OR REPLACE FUNCTION match_lore_embeddings(
  query_embedding vector(1536),
  match_threshold float DEFAULT 0.5,
  match_count int DEFAULT 10
)
RETURNS TABLE (
  id uuid,
  content_text text,
  title text,
  source text,
  metadata jsonb,
  category text,
  similarity float
)
LANGUAGE plpgsql
AS $$
BEGIN
  RETURN QUERY
  SELECT
    le.id,
    le.content_text,
    le.title,
    le.source,
    le.metadata,
    le.category,
    1 - (le.embedding <=> query_embedding) AS similarity
  FROM lore_embeddings le
  WHERE 1 - (le.embedding <=> query_embedding) > match_threshold
  ORDER BY le.embedding <=> query_embedding
  LIMIT match_count;
END;
$$;
