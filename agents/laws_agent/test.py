import chromadb

# Connect to the database
client = chromadb.PersistentClient(path="./lotn_rag_db")

# Get the collection
collection = client.get_or_create_collection(name="laws_of_the_night")

# Check how many documents are in there
count = collection.count()
print(f"Documents in database: {count}")

# Try a quick search
if count > 0:
    results = collection.query(
        query_texts=["What are Samedi weaknesses?"],
        n_results=3
    )
    
    print("\n--- Test Query Results ---")
    for i, doc in enumerate(results['documents'][0]):
        print(f"\nResult {i+1}:")
        print(f"Page: {results['metadatas'][0][i]['page']}")
        print(f"Content: {doc[:200]}...")
else:
    print("No documents found - migration probably failed")

input("\nPress Enter to exit...")