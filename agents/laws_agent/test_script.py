import chromadb
import os

# Change to script directory
script_dir = os.path.dirname(os.path.abspath(__file__))
os.chdir(script_dir)

print(f"Working directory: {os.getcwd()}")

try:
    client = chromadb.PersistentClient(path="./lotn_rag_db")
    collection = client.get_collection(name="laws_of_the_night")
    
    count = collection.count()
    print(f"\nDocuments in database: {count}")
    
    if count > 0:
        print("\n✓ SUCCESS - Database has data!")
        
        # Try a search
        results = collection.query(
            query_texts=["What are the Samedi clan weaknesses?"],
            n_results=3
        )
        
        print("\n--- Test Search Results ---")
        for i, doc in enumerate(results['documents'][0]):
            print(f"\nResult {i+1}:")
            print(doc[:300])
    else:
        print("\n✗ FAILED - Database is empty")
        
except Exception as e:
    print(f"\n✗ ERROR: {e}")
    import traceback
    traceback.print_exc()

input("\nPress Enter to exit...")