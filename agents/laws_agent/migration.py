import os
import chromadb
import json

# Change to the script's directory
script_dir = os.path.dirname(os.path.abspath(__file__))
os.chdir(script_dir)

print(f"Script is in: {script_dir}")
print(f"Working directory set to: {os.getcwd()}")

# Check for Books folder
if not os.path.exists("Books"):
    print(f"ERROR: No Books folder found")
    input("Press Enter to exit...")
    exit()

print(f"Found Books folder")
json_files = [f for f in os.listdir('Books') if f.endswith('.json')]
print(f"JSON files found: {json_files}")

# Initialize Chroma client
client = chromadb.PersistentClient(path="./lotn_rag_db")

# Create collection
collection = client.get_or_create_collection(
    name="laws_of_the_night",
    metadata={"description": "Laws of the Night Revised rulebooks"}
)

# Load the first JSON file
json_file = os.path.join("Books", json_files[0])
print(f"\nLoading: {json_file}")

with open(json_file, "r", encoding="utf-8") as f:
    documents = json.load(f)

print(f"Loaded {len(documents)} documents")

# Prepare data for Chroma
ids = []
texts = []
metadatas = []

for doc in documents:
    ids.append(doc["id"])
    texts.append(doc["content"])
    
    # Flatten metadata for Chroma
    metadata = {
        "source": doc["metadata"]["source"],
        "page": doc["page"],
        "content_type": doc["content_type"],
        "book_code": doc["metadata"]["book_code"],
        "section_title": doc["metadata"].get("section_title", ""),
    }
    metadatas.append(metadata)

print(f"Prepared {len(ids)} entries for database")

# Add to Chroma (ONLY ONCE)
print("Adding to Chroma database...")
collection.add(
    ids=ids,
    documents=texts,
    metadatas=metadatas
)

print(f"Successfully added to database!")

# Check the count
total = collection.count()
print(f"Total documents in database: {total}")

input("\nPress Enter to exit...")